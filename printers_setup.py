import sqlite3

conn = sqlite3.connect('printers.db')
cursor = conn.cursor()

cursor.execute("""
CREATE TABLE IF NOT EXISTS printers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    floor TEXT,
    name TEXT,
    ip TEXT NOT NULL
)
""")

printers = [
    ("6th Floor", "TASKalfa 4054ci", "10.4.0.24"),
    ("3rd Floor", "TASKalfa 4054ci", "10.4.0.22"),
    ("7th Floor (HR)", "TASKalfa 6003i", "10.4.0.15"),
    ("7th Floor (Legal)", "TASKalfa 5054ci", "10.4.0.20"),
    ("3rd Floor", "TASKalfa 4053ci", "10.4.0.14"),
    ("2nd Floor", "TASKalfa 6003i", "10.4.0.17"),
    ("2nd Floor", "Unknown Printer", "10.4.0.18")
]

cursor.executemany(
    "INSERT INTO printers (floor, name, ip) VALUES (?, ?, ?)", printers
)

conn.commit()
conn.close()

print("Printers database created successfully.")