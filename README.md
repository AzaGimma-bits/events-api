# API REST - Gestión de Usuarios y Transacciones

## Descripción

API RESTful desarrollada en Laravel para la gestión de usuarios y transferencias financieras.  
Incluye validaciones de negocio, control de concurrencia y autenticación mediante tokens.

## Requisitos
- PHP 8.1 o superior  
- Composer  
- MySQL o SQL Server  
- Laravel 11  

## Instalción

1. Clonar repositorio
`git clone https://github.com/AzaGimma-bits/events-api.git`
`cd events-api`

1. Instalar dependencias
   `composer install`

2. Configurar entorno
   ###### Editar .env:
- DB_CONNECTION=mysql 
- DB_HOST=127.0.0.1 
- DB_PORT=3307 
- DB_DATABASE=laravel_api_test 
- DB_USERNAME=root 
- DB_PASSWORD=

3. Generar clave
   `php artisan key:generate`

4. Ejecutar migraciones
   `php artisan migrate`

5. Levantar servidor
   `php artisan serve`

#### Pruebas
Se recomienda utilizar Postman para probar los endpoints de la API.
Las solicitudes deben incluir el token de autenticación en los headers para acceder a las rutas protegidas.

### Autenticación

El proyecto utiliza Laravel Sanctum para autenticación basada en tokens.

Nota: Sanctum ya está incluido en el proyecto, no es necesario instalarlo manualmente.

### Login

`POST /api/login`

```js
Body (JSON):

{
"email": "seguridad@test
",
"password": "123456"
}

Respuesta:

{
"token": "1|xxxxx"
}
```


#####  Uso del token

Incluir en lo headers:
Authorization: Bearer TU_TOKEN
Accept: application/json

### Endpoints

##### Públicos
    - POST /api/users
    - POST /api/login

##### Protegidos (auth:sanctum)
###### Usuarios
- GET /api/users
- GET /api/users/{id}
- PUT /api/users/{id}
- DELETE /api/users/{id}

###### Transacciones
- POST /api/transactions
- GET /api/transactions/stats (Total transferido y promedio del monto)
- GET /api/transactions/export

## Reglas

- No se permite transferir más del saldo disponible
- Límite diario de transferencia: 5.000 USD
- Prevención de transacciones duplicadas

## Consideraciones técnicas
- Uso de transacciones (DB::transaction)
- Control de concurrencia mediante lockForUpdate
- Autenticación con Laravel Sanctum
- Exportación CSV con delimitador “;”

## Caso 2

#### Problema

El sistema permite realizar transferencias que superan el límite diario de 5.000 USD.

##### Identificación del origen del problema

Para identificar el origen del problema, comenzaría revisando la lógica de validación del límite diario, verificando si esta se ejecuta fuera de un contexto transaccional.

También analizaría los logs del sistema y realizaría pruebas controladas con múltiples solicitudes concurrentes para reproducir el error. Además, revisaría el flujo completo de la operación, ya que es posible que se esté validando el monto acumulado sin considerar transacciones en curso, lo que puede generar inconsistencias.

Una posible causa sería una condición de carrera. Esto ocurriría debido a múltiples solicitudes validan el límite diario antes de que las transacciones previas se registren en bd.

##### Solución y justificación
Inicialmente, consideré el problema desde la validación lógica, pero luego identifiqué que en escenarios concurrentes era necesario aplicar control de concurrencia. Como solución principal, se implementa el uso de transacciones junto con bloqueo de registros (por ejemplo, mediante lockForUpdate), asegurando que la validación del límite diario se realice sobre datos consistentes. 
Como alternativa, implementaría una cola de procedimiento para serializar las transacciones y evitar ejecuciones simultáneas. Finalmente, como última alternativa, se podría implementar una tabla de acumulado diario por usuario, lo que permitiría optimizar la validación y reducir la complejidad de las consultas.

Estas medidas permiten garantizar la consistencia de los datos en escenarios concurrentes, evitando que múltiples solicitudes superen el límite diario.