from flask import Flask, render_template, request, redirect, flash, jsonify
import sqlite3
import os

app = Flask(__name__)
app.secret_key = "super_secret_key_123"

DB_FILE = "laptops.db"


# ================= DATABASE INIT =================
def init_db():
    conn = sqlite3.connect(DB_FILE)
    cursor = conn.cursor()

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS laptops (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            asset_tag TEXT UNIQUE,
            serial_number TEXT,
            brand TEXT,
            model TEXT,
            department TEXT,
            assigned_to TEXT,
            status TEXT,
            purchase_date TEXT,
            warranty_expiry TEXT
        )
    """)

    conn.commit()
    conn.close()

init_db()


def get_laptops():
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM laptops")
    rows = cursor.fetchall()
    conn.close()
    return rows


# ================= ROUTES =================

@app.route("/")
def home():
    laptops = get_laptops()
    return render_template("home.html", laptops=laptops)


@app.route("/dashboard")
def dashboard():
    laptops = get_laptops()
    return render_template("dashboard.html", laptops=laptops)


@app.route("/admin")
def admin():
    laptops = get_laptops()
    return render_template("admin.html", laptops=laptops)


# ================= ADD =================

@app.route("/admin/add", methods=["GET", "POST"])
def add_laptop():
    if request.method == "POST":
        data = request.form
        try:
            conn = sqlite3.connect(DB_FILE)
            cursor = conn.cursor()
            cursor.execute("""
                INSERT INTO laptops
                (asset_tag, serial_number, brand, model, department,
                 assigned_to, status, purchase_date, warranty_expiry)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            """, (
                data["asset_tag"],
                data["serial_number"],
                data["brand"],
                data["model"],
                data["department"],
                data["assigned_to"],
                data["status"],
                data["purchase_date"],
                data["warranty_expiry"]
            ))
            conn.commit()
            conn.close()

            flash("Laptop added successfully!", "success")
            return redirect("/admin")

        except sqlite3.IntegrityError:
            flash("Asset Tag must be unique!", "error")

    return render_template("add.html")


# ================= EDIT =================

@app.route("/admin/edit/<int:id>", methods=["GET", "POST"])
def edit_laptop(id):
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()

    if request.method == "POST":
        data = request.form
        cursor.execute("""
            UPDATE laptops SET
                asset_tag=?, serial_number=?, brand=?, model=?,
                department=?, assigned_to=?, status=?,
                purchase_date=?, warranty_expiry=?
            WHERE id=?
        """, (
            data["asset_tag"],
            data["serial_number"],
            data["brand"],
            data["model"],
            data["department"],
            data["assigned_to"],
            data["status"],
            data["purchase_date"],
            data["warranty_expiry"],
            id
        ))
        conn.commit()
        conn.close()

        flash("Laptop updated successfully!", "success")
        return redirect("/admin")

    cursor.execute("SELECT * FROM laptops WHERE id=?", (id,))
    laptop = cursor.fetchone()
    conn.close()

    return render_template("edit.html", laptop=laptop)


# ================= DELETE =================

@app.route("/admin/delete/<int:id>", methods=["GET", "POST"])
def delete_laptop(id):
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()

    if request.method == "POST":
        cursor.execute("DELETE FROM laptops WHERE id=?", (id,))
        conn.commit()
        conn.close()
        flash("Laptop deleted successfully!", "success")
        return redirect("/admin")

    cursor.execute("SELECT * FROM laptops WHERE id=?", (id,))
    laptop = cursor.fetchone()
    conn.close()

    return render_template("delete.html", laptop=laptop)


if __name__ == "__main__":
    app.run(debug=True)