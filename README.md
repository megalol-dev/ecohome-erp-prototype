# EcoHome ERP Prototype

## 📌 Descripción del proyecto

Este proyecto es un **prototipo funcional de una aplicación web corporativa** desarrollado para la empresa ficticia **EcoHome**, cuyo objetivo es apoyar un proceso de **transformación digital** desde una perspectiva estratégica y técnica.

El sistema centraliza la gestión de **pedidos, facturas, pagos, stock y usuarios**, eliminando silos de información y sustituyendo el uso de herramientas no corporativas como WhatsApp o hojas de Excel.

Aunque se trata de un prototipo académico, la aplicación está diseñada siguiendo principios reales de **arquitectura empresarial**, actuando como núcleo de un **ERP web escalable**, con posibilidad de ampliación hacia módulos de **CRM** e integración futura con **IoT**.

---

## 🎯 Objetivo del proyecto

- Centralizar la información clave de la empresa en un único sistema
- Mejorar la trazabilidad de pedidos, facturas y pagos
- Facilitar la toma de decisiones basada en datos en tiempo real
- Demostrar el rol del **Chief Digital Officer (CDO)** en la definición y dirección de la tecnología
- Aportar un prototipo funcional como valor añadido a la propuesta estratégica

---

## 🧩 Arquitectura general

- **Backend:** PHP
- **Base de datos:** SQLite
- **Frontend:** HTML + CSS
- **Autenticación:** Sistema de usuarios con roles
- **Acceso:** Aplicación web responsive (PC, tablet, móvil)

La aplicación actúa como:
- **ERP:** gestión interna (pedidos, facturas, stock, pagos)
- **Base para CRM:** gestión de clientes y ventas
- **Preparada para IoT:** futura integración con sensores de stock y consumo

---

## 👥 Roles y permisos

El sistema incluye control de acceso por roles, entre ellos:

- Administrador
- Gestión
- Logística
- RRHH
- Directivos

Cada rol accede únicamente a las secciones necesarias para su trabajo, mejorando la seguridad y la usabilidad.

---

## 🔐 Usuarios de prueba

En la carpeta **`/documentacion`** se incluye un archivo con:

- Usuarios ya creados
- Correos y contraseñas de acceso
- Rol asignado a cada usuario

Esto permite **probar la aplicación directamente** sin necesidad de crear usuarios manualmente.

---

# EcoHome ERP – Estructura del Proyecto (Refactorizado)

Este proyecto es una aplicación web tipo **ERP** desarrollada en PHP, orientada a la gestión interna de una empresa ficticia llamada **EcoHome**.  
Incluye módulos de usuarios, pedidos, stock, facturación e informes, con control de acceso por roles.

Este README describe **exclusivamente la estructura del proyecto** tras la refactorización.

---

## 📁 Estructura de carpetas



```

GESTORAPP/
│
├── app/
│ ├── includes/
│ └── pages/
│ ├── usuarios/
│ ├── pedidos/
│ ├── stock/
│ ├── facturas/
│ └── informes/
│
├── public/
│ └── assets/
│ └── css/
│
├── storage/
│ └── db/
│
├── uploads/
│
├── docs/
│
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── db.php
└── README.md

```


---

## 📂 Descripción de carpetas y archivos

### 🔹 `app/`
Contiene **toda la lógica principal de la aplicación**, separada del punto de entrada público.

#### `app/pages/`
Agrupa las páginas por **módulos funcionales**, evitando archivos sueltos y facilitando el mantenimiento.

- **usuarios/**
  - Gestión de usuarios y empleados
  - Crear, editar y borrar usuarios
  - Control de roles (admin, RRHH, etc.)

- **pedidos/**
  - Creación y visualización de pedidos
  - Detalle de pedidos y sus líneas

- **stock/**
  - Gestión de materiales
  - Entradas, salidas y ajustes de stock
  - Historial de movimientos

- **facturas/**
  - Facturación a clientes y a empresa
  - Facturas pendientes y pagadas
  - Visualización de imágenes de facturas

- **informes/**
  - Informes de facturación y stock
  - Resúmenes y vistas agrupadas
  - Acceso según rol

#### `app/includes/`
Reservado para **código reutilizable**, helpers o componentes comunes  
(actualmente preparado para futuras ampliaciones).

---

### 🔹 `public/`
Recursos públicos accesibles desde el navegador.

- **assets/css/**
  - Hojas de estilo globales de la aplicación

---

### 🔹 `storage/`
Datos internos que **no deben ser públicos**.

- **db/**
  - Base de datos SQLite (`EcoHome.db`)

---

### 🔹 `uploads/`
Almacena archivos subidos por los usuarios:

- Imágenes de facturas de clientes
- Archivos asociados a la gestión documental

---

### 🔹 `docs/`
Documentación auxiliar del proyecto:

- Notas internas
- Consultas SQL
- Apuntes de desarrollo

---

## 📄 Archivos principales en la raíz

- **index.php**  
  Punto de entrada de la aplicación. Redirige según estado de sesión.

- **login.php / logout.php**  
  Autenticación de usuarios y cierre de sesión.

- **dashboard.php**  
  Panel principal tras el login. Muestra accesos según rol.

- **db.php**  
  Configuración centralizada de la conexión a la base de datos.

---

## ✅ Notas finales

- La estructura está pensada para **escalar**, mantener y depurar fácilmente.
- La lógica se mantiene separada de los recursos públicos.
- Todas las rutas y redirecciones fueron adaptadas tras la refactorización.
- Proyecto refactorizado completamente desde una versión inicial no modular.

---

---

## 🚀 Escalabilidad futura

El sistema está diseñado para crecer sin necesidad de rehacer la base:

- Añadir dashboards con KPIs
- Integrar un CRM completo (seguimiento de clientes y proyectos)
- Conectar sensores IoT para control automático de stock
- Exportación de informes financieros
- Integración con sistemas externos

---

## 🎥 Vídeo demostración

El proyecto incluye un vídeo explicativo donde se muestra el funcionamiento de la aplicación:

👉 https://youtu.be/LtaSYJ0hnrg

---

## 📚 Contexto académico

Este proyecto forma parte de una actividad académica orientada a demostrar:
- Visión estratégica de la tecnología
- Conocimiento de ERP, CRM e IoT
- Capacidad para alinear tecnología y negocio
- Proactividad y pensamiento crítico

El prototipo se presenta como **valor añadido**, complementando el análisis y la propuesta estratégica.

---

## 📝 Autor

**José Luis**  
Desarrollador de Aplicaciones Web  
Proyecto académico – DAW


