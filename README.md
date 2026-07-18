# fioreee_barber

MVP de autogestión de turnos para fioreee_barber, desarrollado en PHP puro y MySQL. Está pensado como una primera versión simple, entendible y ampliable.

## Incluye

- Reserva pública por servicio, peluquero, fecha y horario.
- Cálculo de disponibilidad según jornada, descanso, duración y turnos ocupados.
- Confirmación y cancelación mediante enlace privado.
- Panel administrativo responsive.
- Agenda diaria separada por peluquero.
- Gestión de servicios, precios y duración.
- Gestión de peluqueros, días, horarios, descansos y servicios habilitados.
- Gestión de productos y stock.
- Ficha de turno con estados, pago, productos y total.
- Clientes creados automáticamente.
- Configuración básica de la barbería.

## Inicio rápido con Docker

1. Abrí una terminal dentro de la carpeta del proyecto.
2. Ejecutá:

```bash
docker compose up --build
```

3. Abrí:

- Reserva pública: `http://localhost:8080`
- Administración: `http://localhost:8080/admin/login.php`

## Usuario inicial

- Correo: `admin@fioreee.local` #CAMBIADOS EN PRODUCCION
- Contraseña: `admin123`

Cambiá esas credenciales antes de publicar el sistema.

## Datos iniciales

- Servicio: Corte de pelo.
- Precio: $12.000.
- Duración inicial: 30 minutos.
- Peluquera: Fiorella.
- Sin productos cargados inicialmente; pueden agregarse desde Administración.

## Reiniciar la base de datos

El esquema solo se importa al crear el volumen por primera vez. Para borrar todos los datos y volver a los datos iniciales:

```bash
docker compose down -v
docker compose up --build
```

## Estructura

```text
app/                 Conexión, autenticación y funciones
public/              Páginas visibles y panel administrativo
public/api/          Consulta de disponibilidad
public/assets/       CSS y JavaScript
database/schema.sql  Tablas y datos iniciales
```

## Próximas mejoras recomendadas

- Reprogramación desde el enlace del cliente.
- Recordatorios automáticos por WhatsApp o correo.
- Caja diaria, comisiones y reportes más completos.
- Usuarios individuales para cada peluquero.
- Bloqueos por vacaciones o ausencias especiales.
- Varias sucursales.
- Mercado Pago con seña online.
