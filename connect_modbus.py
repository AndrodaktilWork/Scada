from pyModbusTCP.client import ModbusClient
import time
import datetime


SMARTLOGGER_CONFIG = {
    "ip": "172.16.245.219",
    "port": 502,
    "unit_id": 0,
    "registers": {
        "Дата и време UTC": (40000, 2, "U32", "UTC", 1),
        "Град": (40002, 2, "U32", "-", 1),
        "Лятно часово време": (40004, 1, "U16", "-", 1),
        "Часова зона": (40005, 2, "I32", "s", 1),
        "DST статус": (40007, 1, "U16", "-", 1),
        "DST отместване": (40008, 1, "U16", "min", 1),
        "Локално време": (40009, 2, "U32", "epoch", 1),

        "Активна мощност 1": (40420, 2, "U32", "kW", 10),
        "Реактивна мощност 1": (40422, 2, "I32", "kVar", 10),
        "Активна мощност %": (40428, 1, "U16", "%", 10),
        "Фактор на мощност": (40429, 1, "I16", "-", 1000),

        "DC ток": (40500, 1, "I16", "A", 10),
        "Входна мощност": (40521, 2, "U32", "kW", 1000),

        "Активна мощност": (40525, 2, "I32", "kW", 1000),
        "Фактор мощност": (40532, 1, "I16", "-", 1000),
        "Реактивна мощност": (40544, 2, "I32", "kVar", 1000),

        "Обща енергия": (40560, 2, "U32", "kWh", 10),
        "Дневна енергия": (40562, 2, "U32", "kWh", 10),
        "Работно време": (40564, 2, "U32", "h", 10),
    }
}


def read_modbus_value(client, address, count, data_type, unit, scale):
    try:
        regs = client.read_holding_registers(address, count)
        if not regs:
            return "N/A"

        if data_type == "U16":
            value = regs[0]
        elif data_type == "I16":
            value = regs[0] if regs[0] < 32768 else regs[0] - 65536
        elif data_type == "U32":
            value = (regs[0] << 16) + regs[1]
        elif data_type == "I32":
            value = (regs[0] << 16) + regs[1]
            if value > 2147483647:
                value -= 4294967296
        else:
            return "ERR"

        scaled_value = value / scale if scale > 1 else value

        if unit == "UTC":
            return datetime.datetime.utcfromtimestamp(scaled_value).strftime("%d.%m.%Y %H:%M:%S")
        elif unit == "epoch":
            return datetime.datetime.fromtimestamp(scaled_value).strftime("%d.%m.%Y %H:%M:%S")
        elif unit in ["kW", "kVar", "kWh"]:
            return f"{scaled_value:.2f} {unit}"
        elif unit in ["A", "V", "%", "-", "h"]:
            return f"{scaled_value:.2f} {unit}"
        else:
            return f"{scaled_value}"

    except Exception as e:
        return f"ERR ({e})"


def main():
    cfg = SMARTLOGGER_CONFIG
    client = ModbusClient(host=cfg["ip"], port=cfg["port"], unit_id=cfg["unit_id"])
    client.timeout = 5.0

    print(f"🔌 Свързване към SmartLogger: {cfg['ip']}:{cfg['port']} (Unit {cfg['unit_id']}) ...")

    if not client.open():
        print("❌ Неуспешно свързване!")
        return

    print("✅ Успешно свързан! Четене на данни...\n")

    try:
        while True:
            print(f"⏱️ {datetime.datetime.now().strftime('%H:%M:%S')} - четене на данни:")
            for name, (addr, count, dtype, unit, scale) in cfg["registers"].items():
                value = read_modbus_value(client, addr, count, dtype, unit, scale)
                print(f"  {name:<25}: {value}")
            print("-" * 60)
            time.sleep(5)

    except KeyboardInterrupt:
        print("\n🛑 Прекратено от потребителя.")
    finally:
        client.close()


if __name__ == "__main__":
    main()
