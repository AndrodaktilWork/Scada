from flask_login import UserMixin, login_user, LoginManager, login_required, current_user, logout_user
from flask import Flask, render_template, request, url_for, redirect, flash, jsonify
from werkzeug.security import generate_password_hash, check_password_hash
from flask_bootstrap import Bootstrap5
from flask_sqlalchemy import SQLAlchemy
from models import db, Client, Schedule, Invertor, User
from datetime import datetime

app = Flask(__name__)
app.config["SECRET_KEY"] = "mysecret"  
app.config["SQLALCHEMY_DATABASE_URI"] = "postgresql+psycopg2://scada_user:scada_pass@localhost:5432/scada_db"

app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False

Bootstrap5(app)
db.init_app(app)


login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

@login_manager.user_loader
def load_user(user_id):
    return db.session.get(User, int(user_id))

@app.route('/login', methods=["GET", "POST"])
def login():
    if request.method == "POST":
        username = request.form.get('username')
        password = request.form.get('password')
        result = db.session.execute(db.select(User).where(User.username == username))
        user = result.scalar()
        if not user:
            flash("That username does not exist, please try again.")
            return redirect(url_for('login'))

        if not check_password_hash(user.password, password):
            flash('Password incorrect, please try again.')
            return redirect(url_for('login'))
        
        else:
            login_user(user)
            return redirect(url_for('home'))

    return render_template("login.html")

@app.route('/logout')
def logout():
    logout_user()
    return redirect(url_for('login'))

@app.route('/users', methods=["GET"])
def users():
    users = User.query.all()
    return render_template("users.html", users=users) 

@app.route('/users/add', methods=["GET", "POST"])
def add_user():
    if request.method == "POST":
        try:
            scada = True if request.form.get('scada') else False
            control = True if request.form.get('control') else False
            camras = True if request.form.get('camras') else False
            hash_and_salted_password = generate_password_hash(
                request.form.get('password'),
                method='pbkdf2:sha256',
                salt_length=8
            )
            new_user = User(
                name=request.form.get('name'),
                email=request.form.get('email'),
                phone=request.form.get('phone'),
                username=request.form.get('username'),
                password=hash_and_salted_password,
                scada=scada,
                control=control,
                camras=camras
            )

            db.session.add(new_user)
            db.session.commit()

            flash('Потребителят е добавен успешно!', 'success')
            return redirect(url_for('users'))

        except Exception as e:
            db.session.rollback()
            flash('Грешка при добавяне на потребител!', 'danger')
            print(f"Error: {e}")

    return render_template("add_user.html")

@app.route('/users/<int:user_id>/delete', methods=["POST"])
def delete_user(user_id):
    try:
        user = User.query.get_or_404(user_id)
        db.session.delete(user)
        db.session.commit()
        return jsonify({"success": True, "message": "Потребителят е изтрит успешно!"})
    except Exception as e:
        db.session.rollback()
        print(f"Error deleting user: {e}")  # за да видиш грешката в конзолата
        return jsonify({"success": False, "message": "Грешка при изтриване на потребител!"})


@app.route('/')
@login_required
def home():
    clients = Client.query.all()
    return render_template("index.html", clients=clients) 

@app.route("/save_schedule", methods=["POST"])
def save_schedule():
    data = request.json
    client_id = data.get("client_id")
    date_str = data.get("date")
    values = data.get("values")

    date = datetime.strptime(date_str, "%Y-%m-%d").date()

    Schedule.query.filter_by(client_id=client_id, date=date).delete()

    for hour, val in values.items():
        sched = Schedule(client_id=client_id, date=date, hour=int(hour), value=int(val))
        db.session.add(sched)

    db.session.commit()
    return jsonify({"status": "success"})

@app.route('/clients', methods=["GET"])
def clients():
    clients = Client.query.all()
    return render_template("clients.html", clients=clients) 

@app.route('/clients/add', methods=["GET", "POST"])
def add_client():
    if request.method == "POST":
        try:
            new_client = Client(
                name=request.form.get('name'),
                representative=request.form.get('representative'),
                production_kw=float(request.form.get('production_kw', 0)),
                company=request.form.get('company'),
                phone=request.form.get('phone'),
                email=request.form.get('email'),
                alarm=False,
                relay=False,
                telemechanics=False
            )
            db.session.add(new_client)
            db.session.flush() 
            
            model = request.form.get('model')
            if model:
                new_invertor = Invertor(
                    client_id=new_client.id,
                    model=model,
                    client_name=request.form.get('representative'),
                    sn_number=request.form.get('sn_number'),
                    representative=request.form.get('name'),
                    power=request.form.get('power'),
                    oneP_threeP=request.form.get('oneP_threeP'),
                    strings=request.form.get('strings'),
                    panels=request.form.get('panels'),
                    usage=request.form.get('usage'),
                    power_to_zero=request.form.get('power_to_zero'),
                    alarms=request.form.get('alarms')
                )
                db.session.add(new_invertor)
            
            db.session.commit()
            flash('Клиентът и инверторът са добавени успешно!', 'success')
            return redirect(url_for('clients'))
            
        except Exception as e:
            db.session.rollback()
            flash('Грешка при добавяне на клиент!', 'danger')
            print(f"Error: {e}")
    
    return render_template("add_client.html")

@app.route('/clients/<int:client_id>/edit', methods=["GET", "POST"])
def edit_client(client_id):
    client = Client.query.get_or_404(client_id)
    
    invertor = client.invertors[0] if client.invertors else None
    
    if request.method == "POST":
        try:
            client.name = request.form.get('name')
            client.representative = request.form.get('representative')
            client.production_kw = float(request.form.get('production_kw', 0))
            client.company = request.form.get('company')
            client.phone = request.form.get('phone')
            client.email = request.form.get('email')
            
            model = request.form.get('model')
            if model:
                if invertor:
                    invertor.model = model
                    invertor.client_name = request.form.get('representative')
                    invertor.sn_number = request.form.get('sn_number')
                    invertor.representative = request.form.get('name')
                    invertor.power = request.form.get('power')
                    invertor.oneP_threeP = request.form.get('oneP_threeP')
                    invertor.strings = request.form.get('strings')
                    invertor.panels = request.form.get('panels')
                    invertor.usage = request.form.get('usage')
                    invertor.power_to_zero = request.form.get('power_to_zero')
                    invertor.alarms = request.form.get('alarms')
                else:
                    new_invertor = Invertor(
                        client_id=client.id,
                        model=model,
                        client_name=request.form.get('representative'),
                        sn_number=request.form.get('sn_number'),
                        representative=request.form.get('name'),
                        power=request.form.get('power'),
                        oneP_threeP=request.form.get('oneP_threeP'),
                        strings=request.form.get('strings'),
                        panels=request.form.get('panels'),
                        usage=request.form.get('usage'),
                        power_to_zero=request.form.get('power_to_zero'),
                        alarms=request.form.get('alarms')
                    )
                    db.session.add(new_invertor)
            
            db.session.commit()
            flash('Клиентът и инверторът са обновени успешно!', 'success')
            return redirect(url_for('clients'))
            
        except Exception as e:
            db.session.rollback()
            flash('Грешка при обновяване на клиент!', 'danger')
            print(f"Error: {e}")
    
    return render_template("edit_client.html", client=client)

@app.route('/clients/<int:client_id>/delete', methods=["POST"])
def delete_client(client_id):
    try:
        client = Client.query.get_or_404(client_id)
        # Delete all invertors for this client
        Invertor.query.filter_by(client_id=client_id).delete()
        # Delete all schedules for this client
        Schedule.query.filter_by(client_id=client_id).delete()
        db.session.delete(client)
        db.session.commit()
        return jsonify({"success": True, "message": "Клиентът е изтрит успешно!"})
    except Exception as e:
        db.session.rollback()
        return jsonify({"success": False, "message": "Грешка при изтриване на клиент!"})

@app.route('/invertors', methods=["GET"])
def invertors():
    invertors = Invertor.query.all()
    return render_template("invertors.html", invertors=invertors) 

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
    app.run(debug=True, host='0.0.0.0', port=5000)