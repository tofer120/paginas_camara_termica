#include <ESP8266WiFi.h>
#include <WiFiClient.h> 
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <OneWire.h>              // One-wire devices
#include <DallasTemperature.h>    // DS18B20
#include <time.h>

#define PIN_ONEWIRE_2 D5 // Dallas DS18B20 temperature sensors
#define PIN_ONEWIRE D7 // GPIO13 // Dallas DS18B20 temperature sensors
#define PERIODO_MUESTREO 60  // segundos
#define MAX_SENSORES 100      // maximo absoluto de 125 aprox. luego se acaba la memoria dinamica!!!!!!!!!!!!

OneWire  oneWire(PIN_ONEWIRE),oneWire2(PIN_ONEWIRE_2);
DallasTemperature  temperatureSensors(&oneWire),temperatureSensors2(&oneWire2);
DeviceAddress  direccion[MAX_SENSORES];
float temperatura[MAX_SENSORES];

int timeZone= -3*3600;
int dst = 0;
int nsen=0;
int nsen1=0;
int nsen2=0;
int precision=12;
boolean soloUnaVez = true;
const int httpPort = 80;
const char* ssid     = "CAMARA_TERMICA";//##############
const char* password = "contru123";//###########

const char* host = "http://labmedios.ufro.cl";//###########
String url= "http://labmedios.ufro.cl/camara_termica/subir_datos.php";//##############
//const char* host = "http://192.168.1.14";//###########
//String url= "http://192.168.1.14/camara_termica/subir_datos.php";//##############
String str1,str2;
unsigned long tiempoAnterior = 0;


void setup() {
  pinMode(LED_BUILTIN,OUTPUT);
  Serial.begin(115200);
  temperatureSensors.begin();
  delay(100);
  temperatureSensors2.begin();
  Serial.println("------"+String(millis())+"--inicio");
  nsen1=temperatureSensors.getDeviceCount();
  nsen2=temperatureSensors2.getDeviceCount();
  nsen=nsen1+nsen2;
  delay(100);
  Serial.println("------"+String(millis())+"--obtencion conteo de sensores");
///busqueda de direcciones en el bus
  Serial.print("------"+String(millis())+"--obtencion direc:");
  for(int n=0; n!=nsen1; n++){
    oneWire.search(direccion[n]);
    //Serial.println(dir2str(direccion[n]));
    Serial.print(".");
    yield();
    delay (100);
  }
  for(int n=nsen1; n!=nsen; n++){
    oneWire2.search(direccion[n]);
    //Serial.println(dir2str(direccion[n]));
    Serial.print(".");
    yield();
    delay (100);
  }
///se imprime el numero de sensores 
  Serial.println(nsen);
///se asigna la resolucion a cada sensor
  for(int n=0; n!=nsen1; n++){
    temperatureSensors.setResolution(direccion[n], precision, true); 
   delay(10);
  }
  for(int n=nsen1; n!=nsen; n++){
    temperatureSensors2.setResolution(direccion[n], precision, true); 
   delay(10);
  }
  Serial.print("Direccion de muestra : ");
  Serial.println(dir2str(direccion[nsen-1]));
///para enviar los datos por wifi, se necesitan algunas string constantes
  str1 = String("POST ") + url + " HTTP/1.0\r\n"
                         + "Host: " + host + "\r\n" 
                         + "Content-Length:";
  str2 = "\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\n";
///finalmente se inicia la conexion a la red wifi
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
      delay(500);
      Serial.print(".");
    }

  Serial.println();
  Serial.print("WiFi connected\t");  
  Serial.print("IP address: ");
  Serial.println(WiFi.localIP());

  /*configTime(timeZone, dst, "pool.ntp.org","time.nist.gov");
  Serial.println("Esperando hora");
  while(!time(nullptr)){
    Serial.print('T');
    delay(200); 
  }
  time_t now = time(nullptr);
  Serial.println(now());*/
}

void loop() {
  unsigned long tiempoActual = millis(); 
  HTTPClient http; 
  /// esto se ejecuta una vez mientras se espera a llegar al periodo de muestreo
  if(soloUnaVez){
     
    temperatureSensors.requestTemperatures();//se solicitan temperaturas
    Serial.println("------"+String(millis())+"--obtencion temp");
    for(int i = 0; i!= nsen1; i++){
      temperatura[i] = temperatureSensors.getTempC(direccion[i]);
//     Serial.println("direccion: "+dir2str(direccion[i])+"@"+String(temperatura[i])+ " °C");
//      Serial.println(dir2str(direccion[i])+",5,"+(i+1));
//      Serial.print(".");
      delay(10);
    }
      temperatureSensors2.requestTemperatures();
      for(int i = nsen1; i!= nsen; i++){
      temperatura[i] = temperatureSensors2.getTempC(direccion[i]);
//     Serial.println("direccion: "+dir2str(direccion[i])+","+String(temperatura[i]));
//      Serial.println(dir2str(direccion[i])+",5,"+(i+1));
//      Serial.print(".");
      delay(10);
    }
       soloUnaVez = false;
  }

  ///luego se envian los datos al NodeMCU
  
  if(tiempoActual - tiempoAnterior >= 1000*PERIODO_MUESTREO){
    tiempoAnterior = tiempoActual;
 /// se solicita la hora  

    Serial.println("******************"+String(millis())+"[ms]********************");
    for(int i = 0; i!= nsen; i++){
      digitalWrite(LED_BUILTIN,HIGH);
      if(temperatura[i]>-100){
        String data = "direc="+dir2str(direccion[i])+"&temp="+String(temperatura[i]);
//        String data = "direc=hola&temp="+String(temperatura[i]+2);
//        String data = "direc=1&temp=1";
        http.begin(url);
        http.addHeader("Content-Type", "application/x-www-form-urlencoded"); 
        int httpCode = http.POST(data);
//        Serial.print(data);
        String payload = http.getString();
        Serial.print(i);
        Serial.print('\t');
        Serial.print(httpCode);
        Serial.print('\t');
        Serial.print(payload);
        Serial.print('\n');
        http.end();
        delay(10);
      }else{
        Serial.println("sensor "+String(i)+"ha sido desconectado");
      }
      digitalWrite(LED_BUILTIN,LOW);
    }
    Serial.println();
    soloUnaVez = true;
  }
  
}


String dir2str(DeviceAddress deviceAddress){
 String str="";
  for (int i = 0; i < 8; i++){
    // zero pad the address if necessary
    if (deviceAddress[i] < 16){
      str = str+"0";      
    }
    str = str +String(deviceAddress[i], HEX);
  }
  str.toUpperCase();
  return str;
}
/*
void serialEvent(){
  inputString = Serial.readStringUntil(10);
  MCU.println(inputString);
  Serial.println(inputString);
  inputString="";  
}*/
