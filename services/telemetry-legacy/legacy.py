import os
import time
import random
import csv
from datetime import datetime
import psycopg2
from psycopg2 import sql

# Читаем env с дефолтами
CSV_DIR = os.getenv("CSV_OUT_DIR", "/data/csv")
PERIOD = int(os.getenv("GEN_PERIOD_SEC", "300"))

DB_PARAMS = {
    "host": os.getenv("PGHOST", "db"),
    "port": os.getenv("PGPORT", "5432"),
    "dbname": os.getenv("PGDATABASE", "monolith"),
    "user": os.getenv("PGUSER", "monouser"),
    "password": os.getenv("PGPASSWORD", "monopass"),
}

os.makedirs(CSV_DIR, exist_ok=True)

def generate_csv():
    ts = datetime.utcnow().strftime("%Y%m%d_%H%M%S")
    filename = f"telemetry_{ts}.csv"
    filepath = os.path.join(CSV_DIR, filename)

    voltage = round(random.uniform(3.2, 12.6), 2)
    temp = round(random.uniform(-50.0, 80.0), 2)
    recorded_at = datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")

    with open(filepath, "w", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(["recorded_at", "voltage", "temp", "source_file"])
        writer.writerow([recorded_at, voltage, temp, filename])

    print(f"Generated {filename}: voltage={voltage}V, temp={temp}°C")
    return filepath

def copy_to_postgres(filepath):
    conn = None
    try:
        conn = psycopg2.connect(**DB_PARAMS)
        cur = conn.cursor()
        with open(filepath, "csv") as f:
            cur.copy_expert(
                sql.SQL("""
                    COPY telemetry_legacy(recorded_at, voltage, temp, source_file)
                    FROM STDIN WITH (FORMAT csv, HEADER true)
                """),
                f
            )
        conn.commit()
        print(f"Successfully imported {os.path.basename(filepath)} into telemetry_legacy")
    except Exception as e:
        print(f"DB error: {e}")
    finally:
        if conn: conn.close()

if __name__ == "__main__":
    print(f"Telemetry legacy (Python) started, period={PERIOD}s")
    while True:
        try:
            csv_path = generate_csv()
            copy_to_postgres(csv_path)
        except Exception as e:
            print(f"Legacy error: {e}")
        time.sleep(PERIOD)