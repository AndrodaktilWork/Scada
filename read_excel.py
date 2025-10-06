import pandas as pd
from sqlalchemy import create_engine
from datetime import datetime, date
import os

def import_excel_to_db(excel_file_path, db_connection_string, client_id=1):
    """
    Импортира данни от Excel файл в базата данни в таблицата Schedule
    """
    try:
        # Прочитане на Excel файла
        df = pd.read_excel(excel_file_path, sheet_name='Новел 22 ООД', skiprows=3)
        
        # Преименуване на колоните
        df.columns = ['date_from', 'date_to', 'interval_15min', 'power_mw']
        
        # Премахване на празни редове
        df = df.dropna(subset=['date_from', 'power_mw'])
        
        # Конвертиране на дати
        df['date_from'] = pd.to_datetime(df['date_from'])
        df['date_to'] = pd.to_datetime(df['date_to'])
        
        # Създаване на връзка с базата данни
        engine = create_engine(db_connection_string)
        
        # Подготовка на данните за вмъкване в Schedule таблицата
        schedule_data = []
        
        for _, row in df.iterrows():
            # Извличане на дата и час
            date_from = row['date_from']
            power_mw = row['power_mw']
            
            # Конвертиране на MW към проценти (ако е необходимо)
            # Тук предполагам, че искате да запазите стойността в проценти
            # Ако максималната мощност е 1 MW, тогава power_mw * 100 ще даде проценти
            value = int(power_mw * 100)  # Конвертиране в проценти
            
            # Извличане на часа (0-23)
            hour = date_from.hour
            
            # Създаване на запис за Schedule таблицата
            schedule_record = {
                'client_id': client_id,
                'date': date_from.date(),
                'hour': hour,
                'value': value
            }
            
            schedule_data.append(schedule_record)
        
        # Вмъкване на данните в базата данни
        if schedule_data:
            # Създаване на DataFrame от подготовените данни
            schedule_df = pd.DataFrame(schedule_data)
            
            # Вмъкване в базата данни
            schedule_df.to_sql('schedule', engine, if_exists='append', index=False)
            
            print(f"Успешно вмъкнати {len(schedule_data)} записа в таблицата Schedule")
            return True
        
        else:
            print("Няма данни за вмъкване")
            return False
            
    except Exception as e:
        print(f"Грешка при импортиране на данни: {e}")
        return False

# Пример за използване
if __name__ == "__main__":
    # Конфигурация
    EXCEL_FILE_PATH = "новел.xlsx"
    DB_CONNECTION_STRING = "postgresql+psycopg2://scada_user:scada_pass@localhost:5432/scada_db"
    CLIENT_ID = 1  # ID на клиента, за който се въвеждат данните
    
    # Изпълнение на импорта
    success = import_excel_to_db(EXCEL_FILE_PATH, DB_CONNECTION_STRING, CLIENT_ID)
    
    if success:
        print("Импортът завърши успешно!")
    else:
        print("Импортът неуспешен!")