from flask_login import UserMixin, login_user, LoginManager, login_required, current_user, logout_user
from flask import Flask, render_template, request, url_for, redirect, flash, jsonify
from werkzeug.security import generate_password_hash, check_password_hash
from flask_bootstrap import Bootstrap5
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime

app = Flask(__name__)
app.config["SECRET_KEY"] = "mysecret"  
app.config["SQLALCHEMY_DATABASE_URI"] = "postgresql+psycopg2://scada_user:scada_pass@localhost:5432/scada_db"

app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False

Bootstrap5(app)
db = SQLAlchemy(app)

class Plant(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(150), nullable=False)
    representative = db.Column(db.String(120), nullable=True)
    production_kw = db.Column(db.Float, default=0.0)
    alarm = db.Column(db.Boolean, default=False)
    relay = db.Column(db.Boolean, default=False)
    telemechanics = db.Column(db.Boolean, default=False)


class Schedule(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    plant_id = db.Column(db.Integer, db.ForeignKey("plant.id"), nullable=False)
    date = db.Column(db.Date, nullable=False)
    hour = db.Column(db.Integer, nullable=False)
    value = db.Column(db.Integer, default=100)

    plant = db.relationship("Plant", backref="schedules")

with app.app_context():
    db.create_all()


@app.route('/')
def home():
    plants = Plant.query.all()
    return render_template("index.html", plants=plants) 

@app.route("/save_schedule", methods=["POST"])
def save_schedule():
    data = request.json
    plant_id = data.get("plant_id")
    date_str = data.get("date")
    values = data.get("values")

    date = datetime.strptime(date_str, "%Y-%m-%d").date()

    Schedule.query.filter_by(plant_id=plant_id, date=date).delete()

    for hour, val in values.items():
        sched = Schedule(plant_id=plant_id, date=date, hour=int(hour), value=int(val))
        db.session.add(sched)

    db.session.commit()
    return jsonify({"status": "success"})

app.run(debug=True)