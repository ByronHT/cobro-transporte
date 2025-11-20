# CODIGO ARDUINO ESP8266 PARA PRODUCCION

## Configuracion del Hardware

### Componentes Necesarios
- NodeMCU ESP8266 (o similar)
- Lector RFID RC522 (MFRC522)
- Cables dupont
- LED indicador (opcional)
- Buzzer (opcional)

### Conexiones

| MFRC522 | ESP8266 |
|---------|---------|
| SDA     | D8 (GPIO15) |
| SCK     | D5 (GPIO14) |
| MOSI    | D7 (GPIO13) |
| MISO    | D6 (GPIO12) |
| GND     | GND |
| RST     | D3 (GPIO0) |
| 3.3V    | 3.3V |

---

## CODIGO COMPLETO PARA PRODUCCION

```cpp
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>
#include <MFRC522.h>
#include <SPI.h>
#include <ArduinoJson.h>

// =========================================
// CONFIGURACION - MODIFICAR SEGUN TU SETUP
// =========================================

// WiFi del chofer (compartido desde celular)
const char* ssid = "WiFi_Chofer_1";
const char* password = "12345678";

// URL del servidor en Railway
// IMPORTANTE: Reemplaza con tu URL real de Railway
const char* server_url = "https://tu-proyecto.up.railway.app";

// ID del bus - UNICO POR CADA SCANNER
const int BUS_ID = 1;

// =========================================
// CONFIGURACION DE HARDWARE
// =========================================

#define SS_PIN D8
#define RST_PIN D3

// Pines opcionales para feedback
#define LED_SUCCESS D1  // LED verde (opcional)
#define LED_ERROR D2    // LED rojo (opcional)
#define BUZZER_PIN D4   // Buzzer (opcional)

// =========================================
// ESTADOS DEL DISPOSITIVO
// =========================================

enum DeviceState {
    WAITING_FOR_COMMAND,  // Esperando que conductor inicie viaje
    TRIP_ACTIVE           // Viaje activo, procesando tarjetas
};

DeviceState currentState = WAITING_FOR_COMMAND;

// =========================================
// VARIABLES GLOBALES
// =========================================

MFRC522 rfid(SS_PIN, RST_PIN);
std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);

long active_trip_id = 0;
long current_command_id = 0;
unsigned long lastCommandCheck = 0;
const long commandCheckInterval = 3000; // Polling cada 3 segundos

// =========================================
// SETUP - INICIALIZACION
// =========================================

void setup() {
    Serial.begin(115200);
    delay(500);

    // Banner de inicio
    Serial.println("\n=========================================");
    Serial.println("   SISTEMA INTERFLOW - SCANNER v2.0");
    Serial.println("=========================================");
    Serial.print("Bus ID: ");
    Serial.println(BUS_ID);
    Serial.println();

    // Inicializar pines opcionales
    pinMode(LED_SUCCESS, OUTPUT);
    pinMode(LED_ERROR, OUTPUT);
    pinMode(BUZZER_PIN, OUTPUT);
    digitalWrite(LED_SUCCESS, LOW);
    digitalWrite(LED_ERROR, LOW);

    // Inicializar SPI y RFID
    SPI.begin();
    rfid.PCD_Init();
    rfid.PCD_SetAntennaGain(rfid.RxGain_max);

    // Configurar cliente HTTPS
    // NOTA: setInsecure() ignora validacion SSL
    // Para produccion real, usar certificado
    client->setInsecure();

    // Conectar a WiFi
    connectToWiFi();

    Serial.println("\n>>> MODO: ESPERANDO COMANDOS <<<");
    Serial.println("Conductor debe iniciar viaje desde la app\n");
}

// =========================================
// LOOP PRINCIPAL
// =========================================

void loop() {
    // Verificar conexion WiFi
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi desconectado. Reconectando...");
        connectToWiFi();
    }

    // Polling de comandos cada 3 segundos
    if (millis() - lastCommandCheck > commandCheckInterval) {
        checkServerForCommands();
        lastCommandCheck = millis();
    }

    // Procesar segun estado actual
    switch (currentState) {
        case WAITING_FOR_COMMAND:
            // Parpadear LED para indicar espera
            blinkLED(LED_ERROR, 1);
            break;

        case TRIP_ACTIVE:
            // Procesar tarjetas RFID
            processPassengerPayment();
            break;
    }

    delay(50);
}

// =========================================
// CONEXION WIFI
// =========================================

void connectToWiFi() {
    Serial.print("Conectando a WiFi: ");
    Serial.println(ssid);

    WiFi.begin(ssid, password);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\nWiFi conectado!");
        Serial.print("IP: ");
        Serial.println(WiFi.localIP());

        // Indicar exito
        digitalWrite(LED_SUCCESS, HIGH);
        delay(500);
        digitalWrite(LED_SUCCESS, LOW);
    } else {
        Serial.println("\nERROR: No se pudo conectar a WiFi");
        Serial.println("Verificar nombre y password");

        // Indicar error
        for(int i = 0; i < 5; i++) {
            digitalWrite(LED_ERROR, HIGH);
            delay(200);
            digitalWrite(LED_ERROR, LOW);
            delay(200);
        }
    }
}

// =========================================
// POLLING DE COMANDOS
// =========================================

void checkServerForCommands() {
    HTTPClient http;
    String url = String(server_url) + "/api/device/command/" + String(BUS_ID);

    http.begin(*client, url);
    http.addHeader("Accept", "application/json");

    int httpCode = http.GET();

    if (httpCode == HTTP_CODE_OK) {
        String payload = http.getString();
        StaticJsonDocument<512> doc;

        if (deserializeJson(doc, payload) == DeserializationError::Ok) {
            const char* command = doc["command"] | "none";

            // Comando: Iniciar viaje
            if (String(command) == "start_trip") {
                current_command_id = doc["command_id"] | 0;
                active_trip_id = doc["trip_id"] | 0;

                Serial.println("\n=============================");
                Serial.println("COMANDO: INICIAR VIAJE");
                Serial.print("Trip ID: ");
                Serial.println(active_trip_id);
                Serial.println("=============================\n");

                if (active_trip_id > 0) {
                    currentState = TRIP_ACTIVE;
                    Serial.println(">>> MODO: COBRANDO <<<");
                    Serial.println("Listo para procesar tarjetas\n");

                    // Feedback de inicio
                    successFeedback();
                    markCommandAsCompleted();
                }
            }
            // Comando: Finalizar viaje
            else if (String(command) == "end_trip") {
                current_command_id = doc["command_id"] | 0;

                Serial.println("\n=============================");
                Serial.println("COMANDO: FINALIZAR VIAJE");
                Serial.println("=============================\n");

                active_trip_id = 0;
                currentState = WAITING_FOR_COMMAND;
                Serial.println(">>> MODO: ESPERANDO <<<\n");

                // Feedback de fin
                for(int i = 0; i < 3; i++) {
                    digitalWrite(LED_SUCCESS, HIGH);
                    delay(100);
                    digitalWrite(LED_SUCCESS, LOW);
                    delay(100);
                }

                markCommandAsCompleted();
            }
            // Sin comandos pendientes
            // No hacer nada, mantener estado actual
        }
    } else if (httpCode == 401) {
        Serial.println("ERROR: Token invalido");
    } else if (httpCode < 0) {
        Serial.print("ERROR conexion: ");
        Serial.println(http.errorToString(httpCode));
    }

    http.end();
}

// =========================================
// MARCAR COMANDO COMPLETADO
// =========================================

void markCommandAsCompleted() {
    if (current_command_id == 0) return;

    HTTPClient http;
    String url = String(server_url) + "/api/device/command/" +
                 String(current_command_id) + "/complete";

    http.begin(*client, url);
    http.addHeader("Content-Type", "application/json");

    int httpCode = http.POST("{}");

    if (httpCode == 200) {
        Serial.println("Comando confirmado al servidor");
    }

    http.end();
    current_command_id = 0;
}

// =========================================
// PROCESAMIENTO DE PAGO
// =========================================

void processPassengerPayment() {
    // Verificar si hay tarjeta presente
    if (!rfid.PICC_IsNewCardPresent()) {
        delay(100);
        return;
    }

    // Leer datos de la tarjeta
    if (!rfid.PICC_ReadCardSerial()) {
        Serial.println("Error leyendo tarjeta");
        errorFeedback();
        delay(100);
        return;
    }

    Serial.println("\n-----------------------------");
    Serial.println("TARJETA DETECTADA");

    // Construir UID de la tarjeta
    String uid = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
        uid += String(rfid.uid.uidByte[i] < 0x10 ? "0" : "");
        uid += String(rfid.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();

    Serial.print("UID: ");
    Serial.println(uid);

    // Verificar que hay viaje activo
    if (active_trip_id == 0) {
        Serial.println("ERROR: No hay viaje activo");
        errorFeedback();
        rfid.PICC_HaltA();
        rfid.PCD_StopCrypto1();
        delay(2000);
        return;
    }

    // Enviar solicitud de pago al servidor
    HTTPClient http;
    String url = String(server_url) + "/api/payment/process";

    http.begin(*client, url);
    http.addHeader("Content-Type", "application/json");

    // Crear JSON con datos del pago
    StaticJsonDocument<256> doc;
    doc["uid"] = uid;
    doc["trip_id"] = active_trip_id;

    String body;
    serializeJson(doc, body);

    Serial.println("Procesando pago...");

    int httpCode = http.POST(body);

    if (httpCode > 0) {
        String payload = http.getString();
        StaticJsonDocument<512> response;

        if (deserializeJson(response, payload) == DeserializationError::Ok) {
            const char* status = response["status"] | "unknown";

            Serial.print("Estado: ");
            Serial.println(status);

            if (String(status) == "success") {
                Serial.println("=============================");
                Serial.println("   PAGO EXITOSO!");
                Serial.print("   Nuevo saldo: Bs. ");
                Serial.println(response["new_balance"].as<String>());
                Serial.println("=============================");

                successFeedback();
            } else {
                const char* message = response["message"] | "Error desconocido";
                Serial.println("-----------------------------");
                Serial.println("PAGO RECHAZADO");
                Serial.print("Razon: ");
                Serial.println(message);
                Serial.println("-----------------------------");

                errorFeedback();
            }
        }
    } else {
        Serial.print("Error HTTP: ");
        Serial.println(httpCode);
        errorFeedback();
    }

    http.end();

    // Finalizar comunicacion con tarjeta
    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();

    // Delay para evitar lecturas duplicadas
    delay(2000);
}

// =========================================
// FUNCIONES DE FEEDBACK
// =========================================

void successFeedback() {
    // LED verde + beep corto
    digitalWrite(LED_SUCCESS, HIGH);
    tone(BUZZER_PIN, 2000, 100);
    delay(200);
    digitalWrite(LED_SUCCESS, LOW);
}

void errorFeedback() {
    // LED rojo + beep largo
    digitalWrite(LED_ERROR, HIGH);
    tone(BUZZER_PIN, 500, 500);
    delay(600);
    digitalWrite(LED_ERROR, LOW);
}

void blinkLED(int pin, int times) {
    for(int i = 0; i < times; i++) {
        digitalWrite(pin, HIGH);
        delay(50);
        digitalWrite(pin, LOW);
        delay(950);
    }
}
```

---

## CONFIGURACION POR BUS

Cada bus necesita su propia configuracion. Cambia estos valores antes de subir:

### Bus 1:
```cpp
const char* ssid = "WiFi_Chofer_1";
const char* password = "password1";
const int BUS_ID = 1;
```

### Bus 2:
```cpp
const char* ssid = "WiFi_Chofer_2";
const char* password = "password2";
const int BUS_ID = 2;
```

### Bus 3:
```cpp
const char* ssid = "WiFi_Chofer_3";
const char* password = "password3";
const int BUS_ID = 3;
```

---

## CONFIGURAR HOTSPOT DEL CHOFER

El chofer debe compartir internet desde su celular:

### Android:
1. Ajustes -> Red e Internet -> Zona WiFi portatil
2. Configurar nombre y password
3. Activar

### iPhone:
1. Ajustes -> Compartir Internet
2. Activar WiFi
3. Configurar password

**El nombre y password deben coincidir con lo programado en el Arduino.**

---

## SUBIR CODIGO AL ARDUINO

### Requisitos:
- Arduino IDE instalado
- Libreria ESP8266 instalada
- Librerias: ArduinoJson, MFRC522

### Instalar Librerias:

1. Arduino IDE -> Herramientas -> Administrar Bibliotecas
2. Buscar e instalar:
   - `ArduinoJson` by Benoit Blanchon
   - `MFRC522` by GithubCommunity

### Configurar Placa:

1. Herramientas -> Placa -> ESP8266 Boards -> NodeMCU 1.0
2. Herramientas -> Puerto -> (seleccionar COM del ESP8266)
3. Herramientas -> Upload Speed -> 115200

### Subir:

1. Conectar ESP8266 por USB
2. Click en "Subir" (flecha derecha)
3. Esperar "Done uploading"

---

## MONITOR SERIAL

Para ver los mensajes del Arduino:

1. Herramientas -> Monitor Serie
2. Seleccionar 115200 baud
3. Veras mensajes como:

```
=========================================
   SISTEMA INTERFLOW - SCANNER v2.0
=========================================
Bus ID: 1

Conectando a WiFi: WiFi_Chofer_1
......
WiFi conectado!
IP: 192.168.43.15

>>> MODO: ESPERANDO COMANDOS <<<
Conductor debe iniciar viaje desde la app
```

---

## SOLUCIONAR PROBLEMAS

### No conecta a WiFi

1. Verificar nombre exacto (mayusculas/minusculas)
2. Verificar password
3. Verificar que hotspot este activo
4. Reiniciar ESP8266

### No recibe comandos

1. Verificar URL del servidor
2. Verificar que BUS_ID exista en la base de datos
3. Verificar conexion a internet del hotspot
4. Revisar logs en Monitor Serial

### Tarjeta no se lee

1. Verificar conexiones del MFRC522
2. Verificar alimentacion 3.3V (NO 5V)
3. Acercar tarjeta mas
4. Probar con otra tarjeta

### Pago rechazado

1. Verificar que hay viaje activo
2. Verificar saldo de la tarjeta
3. Verificar UID en base de datos
4. Revisar logs del servidor

---

## SEGURIDAD EN PRODUCCION

Para un sistema de produccion real, considera:

### 1. Validar Certificado SSL

```cpp
// En lugar de setInsecure():
BearSSL::X509List cert(serverCert);
client->setTrustAnchors(&cert);
```

### 2. Token de Dispositivo

```cpp
// Agregar header de autorizacion:
http.addHeader("Authorization", "Bearer " + String(device_token));
```

### 3. Encriptar UID

```cpp
// No enviar UID en texto plano
// Usar hash o encriptacion
```

---

## ESQUEMA DE CONEXIONES

```
                  +-------------------+
                  |    ESP8266        |
                  |   (NodeMCU)       |
                  +-------------------+
                          |
        +-----------------+-----------------+
        |                 |                 |
   +----+----+      +-----+-----+    +------+------+
   | MFRC522 |      |  LEDs     |    |   Buzzer    |
   |  RFID   |      | (opcional)|    | (opcional)  |
   +---------+      +-----------+    +-------------+

Conexiones MFRC522:
- SDA -> D8
- SCK -> D5
- MOSI -> D7
- MISO -> D6
- RST -> D3
- 3.3V -> 3.3V
- GND -> GND

LEDs (opcional):
- LED Verde -> D1
- LED Rojo -> D2

Buzzer (opcional):
- Signal -> D4
```

---

**El Arduino esta listo para conectarse a Railway!**
