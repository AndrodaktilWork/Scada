from flask_login import UserMixin, login_user, LoginManager, login_required, current_user, logout_user
from flask import Flask, render_template, request, url_for, redirect, flash, jsonify
from werkzeug.security import generate_password_hash, check_password_hash
from flask_bootstrap import Bootstrap5
from flask_sqlalchemy import SQLAlchemy
from models import db, Client, Schedule, Invertor, User
from datetime import datetime
from users_blueprint import users_bp
from clients_blueprint import clients_bp, start_modbus_background

app = Flask(__name__)
app.config["SECRET_KEY"] = "mysecret"  
app.config["SQLALCHEMY_DATABASE_URI"] = "postgresql+psycopg2://scada_user:scada_pass@localhost:5432/scada_db"
app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False
app.register_blueprint(users_bp)
app.register_blueprint(clients_bp)

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

@app.route('/')
@login_required
def home():
    clients = Client.query.all()
    return render_template("dashboard.html", clients=clients) 

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

@app.route('/invertors', methods=["GET"])
def invertors():
    invertors = Invertor.query.all()
    return render_template("invertors.html", invertors=invertors) 

if __name__ == '__main__':
    with app.app_context():
        start_modbus_background(app)
        db.create_all()
    app.run(debug=True, host='0.0.0.0', port=5000)