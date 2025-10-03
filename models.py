from flask_sqlalchemy import SQLAlchemy
from flask_login import UserMixin

db = SQLAlchemy()

class Client(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(150), nullable=False)
    representative = db.Column(db.String(120), nullable=True)
    production_kw = db.Column(db.Float, default=0.0)
    alarm = db.Column(db.Boolean, default=False)
    relay = db.Column(db.Boolean, default=False)
    telemechanics = db.Column(db.Boolean, default=False)
    phone = db.Column(db.String(159), default=False)
    email = db.Column(db.String(159), default=False)
    company = db.Column(db.String(159), default=False)

class Schedule(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    date = db.Column(db.Date, nullable=False)
    hour = db.Column(db.Integer, nullable=False)
    value = db.Column(db.Integer, default=100)
    client = db.relationship("Client", backref="schedules")

class Invertor(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    model = db.Column(db.String(159), default=False)
    client_id = db.Column(db.Integer, db.ForeignKey("client.id"), nullable=False)
    sn_number = db.Column(db.String(159), default=False)
    client = db.relationship("Client", backref="invertors")
    representative = db.Column(db.String(120), nullable=True)
    power = db.Column(db.Integer, default=False)
    oneP_threeP = db.Column(db.String(159), default=False)
    strings = db.Column(db.Integer, default=False)
    panels = db.Column(db.Integer, default=False)
    usage = db.Column(db.String(159), default=False)
    power_to_zero = db.Column(db.Integer, default=False)
    alarms = db.Column(db.String(159), default=False)

class User(UserMixin, db.Model):
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(100), nullable=True)
    password = db.Column(db.String(100), default=False)
    username = db.Column(db.String(1000), default=False)