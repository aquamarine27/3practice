import os
import time
import random
import csv
from datetime import datetime, timezone
import psycopg2
from psycopg2 import sql
import pandas as pd 


CSV_DIR = os.getenv("CSV_OUT_DIR", "/data/csv")
XLSX_DIR = os.getenv("XLSX_OUT_DIR", "/data/xlsx")
PERIOD = int(os.getenv("GEN_PERIOD_SEC", "300"))

DB_PARAMS = {
    "host": os.getenv("PGHOST", "db"),
    "port": os.getenv("PGPORT", "5432"),
    "dbname": os.getenv("PGDATABASE", "monolith"),
    "user": os.getenv("PGUSER", "monouser"),
    "password": os.getenv("PGPASSWORD", "monopass"),
}


os.makedirs(CSV_DIR, exist_ok=True)
os.makedirs(XLSX_DIR, exist_ok=True)


def generate_data_row():
    """Генерирует одну строку данных, используя DATETIME объект."""
    recorded_at = datetime.now(timezone.utc).replace(microsecond=0)
    voltage = round(random.uniform(3.2, 12.6), 2)
    temp = round(random.uniform(-50.0, 80.0), 2)
    telemetry_id = random.randint(100000, 999999) 

    is_active = random.choice([True, False])
    
    statuses = ["NOMINAL", "DEGRADED", "OFFLINE", "MAINTENANCE"]
    mission_status = random.choice(statuses)

    return {
        "telemetry_id": telemetry_id,
        "recorded_at": recorded_at, 
        "is_active": is_active,
        "voltage": voltage,
        "temp": temp,
        "mission_status": mission_status,
    }


def generate_csv():
    """Генерирует CSV-файл, конвертируя DATETIME в ISO-строку."""
    data = generate_data_row()
    
    ts_file = datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S")
    filename = f"telemetry_{ts_file}.csv"
    filepath = os.path.join(CSV_DIR, filename)

    header = list(data.keys())
    row_values = list(data.values())
    
    csv_row = list(row_values) 

    # Преобразуем DATETIME в ISO-строку для CSV
    csv_row[header.index("recorded_at")] = data["recorded_at"].isoformat()
    # Логические блоки в CSV: 'TRUE' / 'FALSE'
    csv_row[header.index("is_active")] = "TRUE" if data["is_active"] else "FALSE" 

    with open(filepath, "w", newline="") as f:
        writer = csv.writer(f)
        writer.writerow(header)
        writer.writerow(csv_row)

    print(f"Generated CSV {filename}: ID={data['telemetry_id']}, Status={data['mission_status']}")
    return filepath, data


def generate_xlsx(data):
    """Генерирует XLSX-файл, используя Pandas и XlsxWriter (п. 2.3)."""
    ts_file = datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S")
    filename = f"telemetry_{ts_file}.xlsx"
    filepath = os.path.join(XLSX_DIR, filename)

    
    df = pd.DataFrame([data])
    

    try:
        df.to_excel(filepath, index=False, sheet_name='Telemetry Data', engine='xlsxwriter')
        print(f"Generated XLSX {filename} (Using Pandas/XlsxWriter)")
    except Exception as e:
        print(f"XLSX generation error (Pandas): {e}")


def copy_to_postgres(filepath, data):
    """Импортирует данные из CSV в PostgreSQL с учетом новых колонок."""
    
    columns = list(data.keys())
    
    temp_filepath = filepath + ".tmp"
    
    
    with open(filepath, 'r', newline='') as infile, open(temp_filepath, 'w', newline='') as outfile:
        reader = csv.reader(infile)
        writer = csv.writer(outfile)
        
        try:
            header_row = next(reader)
            writer.writerow(header_row) 
        except StopIteration:
            print("CSV file is empty.")
            return

        is_active_idx = header_row.index('is_active') if 'is_active' in header_row else -1
        
        for row in reader:
            if is_active_idx != -1:
                if row[is_active_idx].upper() == 'TRUE':
                    row[is_active_idx] = 't'
                elif row[is_active_idx].upper() == 'FALSE':
                    row[is_active_idx] = 'f'
            writer.writerow(row)


   
    conn = None
    try:
        conn = psycopg2.connect(**DB_PARAMS)
        cur = conn.cursor()
        
        col_sql = sql.SQL(', ').join(map(sql.Identifier, columns))
        
        with open(temp_filepath, "r") as f:
            copy_command = sql.SQL("""
                COPY telemetry_legacy({})
                FROM STDIN WITH (FORMAT csv, HEADER true)
            """).format(col_sql)

            cur.copy_expert(copy_command, f)
            
        conn.commit()
        print(f"Successfully imported {os.path.basename(filepath)} into telemetry_legacy")
        
    except Exception as e:
        print(f"DB error: {e}")
    finally:
        if conn: conn.close()
        # Удаляем временный файл
        if os.path.exists(temp_filepath):
            os.remove(temp_filepath)


if __name__ == "__main__":
    print(f"Telemetry legacy (Python) started, period={PERIOD}s")
    while True:
        try:
            csv_path, data = generate_csv()
            
            copy_to_postgres(csv_path, data)
            
            generate_xlsx(data)
            
        except Exception as e:
            print(f"Legacy error: {e}")
        time.sleep(PERIOD)