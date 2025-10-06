
from flask import Blueprint, render_template, request, redirect, url_for, flash, jsonify, make_response
from flask_login import login_required
from models import db, Client, Schedule, Invertor, User
from connect_modbus import SMARTLOGGER_CONFIG, read_modbus_value
from pyModbusTCP.client import ModbusClient
import threading
import copy
import json
import time

clients_bp = Blueprint('clients', __name__)
inverter_data = []
cfg_list = []
modbus_threads = []


def make_config_from_invertor(inv):
    cfg = SMARTLOGGER_CONFIG.copy()
    cfg["id"] = inv.id
    cfg["ip"] = inv.ip
    cfg["port"] = int(inv.port)
    cfg["unit_id"] = int(inv.unit_id)
    return cfg

# --- Възстановена функция за стартиране на Modbus нишките ---
def start_modbus_threads():
    global inverter_data, cfg_list, modbus_threads
    invertors = Invertor.query.all()
    cfg_list = [make_config_from_invertor(inv) for inv in invertors]
    inverter_data = [{} for _ in cfg_list]
    modbus_threads = []
    for idx, cfg in enumerate(cfg_list):
        t = threading.Thread(target=update_inverter_data, args=(idx, copy.deepcopy(cfg)), daemon=True)
        t.start()
        modbus_threads.append(t)

# Фонов процес за динамично обновяване на нишките
def modbus_refresh_loop(app, interval=60):
    while True:
        with app.app_context():
            start_modbus_threads()
        time.sleep(interval)

# Стартирай първоначално нишките и фоновия процес
def start_modbus_background(app):
    start_modbus_threads()
    threading.Thread(target=modbus_refresh_loop, args=(app, 60), daemon=True).start()

def update_inverter_data(idx, cfg):
    while True:
        client = ModbusClient(host=cfg["ip"], port=cfg["port"], unit_id=cfg["unit_id"])
        client.timeout = 5.0
        if client.open():
            data = {}
            for name, (addr, count, dtype, unit, scale) in cfg["registers"].items():
                value = read_modbus_value(client, addr, count, dtype, unit, scale)
                data[name] = value
            inverter_data[idx] = data
            client.close()
        else:
            inverter_data[idx] = {"error": "Неуспешно свързване"}
        time.sleep(5)

@clients_bp.route('/api/inverters')
def api_inverters():
    invertors = Invertor.query.all()
    result = {}
    for idx, inv in enumerate(invertors):
        result[str(inv.id)] = inverter_data[idx] if idx < len(inverter_data) else {"error": "Няма данни"}
    response = make_response(json.dumps(result, ensure_ascii=False))
    response.mimetype = 'application/json'
    return response

@clients_bp.route('/client_details/<int:invertor_id>', methods=["GET"])
@login_required
def client_details(invertor_id):
    invertors = Invertor.query.all()
    idx = None
    invertor_obj = None
    for i, inv in enumerate(invertors):
        if inv.id == invertor_id:
            idx = i
            invertor_obj = inv
            break
    data = inverter_data[idx] if idx is not None and idx < len(inverter_data) else {"error": "Няма данни"}
    return render_template("client_details.html", invertor=invertor_obj, data=data)

@clients_bp.route('/clients', methods=["GET"])
@login_required
def clients():
    clients = Client.query.all()
    return render_template("clients.html", clients=clients) 

@clients_bp.route('/clients/add', methods=["GET", "POST"])
@login_required
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
                username =request.form.get('username'),
                password =request.form.get('password'),
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
                    alarms=request.form.get('alarms'),
                    ip=request.form.get('ip'),
                    unit_id=request.form.get('unit_id'),
                    port=request.form.get('port')
                )
                db.session.add(new_invertor)
            
            db.session.commit()
            flash('Клиентът и инверторът са добавени успешно!', 'success')
            return redirect(url_for('clients.clients'))
            
        except Exception as e:
            db.session.rollback()
            flash('Грешка при добавяне на клиент!', 'danger')
            print(f"Error: {e}")
    
    return render_template("add_client.html")

@clients_bp.route('/clients/<int:client_id>/edit', methods=["GET", "POST"])
@login_required
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
            client.username = request.form.get('username')
            client.password = request.form.get('password')
            
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
                    invertor.ip = request.form.get('ip')
                    invertor.unit_id = request.form.get('unit_id')
                    invertor.port = request.form.get('port')
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
                        alarms=request.form.get('alarms'),
                        ip=request.form.get('ip'),
                        unit_id=request.form.get('unit_id'),
                        port=request.form.get('port')
                    )
                    db.session.add(new_invertor)
            
            db.session.commit()
            flash('Клиентът и инверторът са обновени успешно!', 'success')
            return redirect(url_for('clients.clients'))
            
        except Exception as e:
            db.session.rollback()
            flash('Грешка при обновяване на клиент!', 'danger')
            print(f"Error: {e}")
    
    return render_template("edit_client.html", client=client)

@clients_bp.route('/clients/<int:client_id>/delete', methods=["POST"])
@login_required
def delete_client(client_id):
    try:
        client = Client.query.get_or_404(client_id)
        Invertor.query.filter_by(client_id=client_id).delete()
        Schedule.query.filter_by(client_id=client_id).delete()
        db.session.delete(client)
        db.session.commit()
        return jsonify({"success": True, "message": "Клиентът е изтрит успешно!"})
    except Exception as e:
        db.session.rollback()
        return jsonify({"success": False, "message": "Грешка при изтриване на клиент!"})