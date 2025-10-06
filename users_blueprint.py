from flask import Blueprint, render_template, request, redirect, url_for, flash, jsonify
from models import db, User
from werkzeug.security import generate_password_hash, check_password_hash

users_bp = Blueprint('users', __name__)

@users_bp.route('/users', methods=["GET"])
def users():
    users = User.query.all()
    return render_template("users.html", users=users) 

@users_bp.route('/users/add', methods=["GET", "POST"])
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
            return redirect(url_for('users.users'))

        except Exception as e:
            db.session.rollback()
            flash('Грешка при добавяне на потребител!', 'danger')
            print(f"Error: {e}")

    return render_template("add_user.html")

@users_bp.route('/users/<int:user_id>/edit', methods=["GET", "POST"])
def edit_user(user_id):
    user = User.query.get_or_404(user_id)
    if request.method == "POST":
        try:
            scada = True if request.form.get('scada') else False
            control = True if request.form.get('control') else False
            camras = True if request.form.get('camras') else False
            hash_and_salted_password = user.password
            if(request.form.get('password') != ""):
                hash_and_salted_password = generate_password_hash(
                    request.form.get('password'),
                    method='pbkdf2:sha256',
                    salt_length=8
                )
            user.name=request.form.get('name'),
            user.email=request.form.get('email'),
            user.phone=request.form.get('phone'),
            user.username=request.form.get('username'),
            user.password=hash_and_salted_password,
            user.scada = bool(request.form.get('scada'))
            user.camras = bool(request.form.get('camras'))
            user.control = bool(request.form.get('control'))
            
            db.session.commit()
            flash('Потребителят е обновен успешно!', 'success')
            return redirect(url_for('users.users'))
            
        except Exception as e:
            db.session.rollback()
            flash('Грешка при обновяване на клиент!', 'danger')
            print(f"Error: {e}")

    return render_template("edit_user.html", user=user)

@users_bp.route('/users/<int:user_id>/delete', methods=["POST"])
def delete_user(user_id):
    try:
        user = User.query.get_or_404(user_id)
        db.session.delete(user)
        db.session.commit()
        return jsonify({"success": True, "message": "Потребителят е изтрит успешно!"})
    except Exception as e:
        db.session.rollback()
        print(f"Error deleting user: {e}") 
        return jsonify({"success": False, "message": "Грешка при изтриване на потребител!"})