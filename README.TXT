================================================================================
                              SISClinic
          Sistema Interno de Gestión de Pacientes y Fisioterapia
================================================================================

DESCRIPCIÓN
--------------------------------------------------------------------------------
SISClinic es una aplicación web interna desarrollada para centros de
fisioterapia y rehabilitación física. Permite al personal profesional
gestionar pacientes, registrar evaluaciones clínicas y llevar un
seguimiento detallado de las sesiones de tratamiento.

El sistema está diseñado exclusivamente para uso del personal clínico
(Administrador y Fisioterapeutas). No incluye portal de pacientes, pagos,
facturación, chat ni funcionalidades móviles.

Proyecto académico para la asignatura "Desarrollo de Aplicaciones Web II".


--------------------------------------------------------------------------------
REQUISITOS DEL SISTEMA
--------------------------------------------------------------------------------
- XAMPP (con Apache y MySQL activos)
- PHP 7.4 o superior (probado en PHP 8.2)
- MySQL 5.7 o superior (probado en MariaDB incluido con XAMPP)
- Navegador web moderno (Chrome, Firefox, Edge, Brave)


--------------------------------------------------------------------------------
INSTALACIÓN PASO A PASO
--------------------------------------------------------------------------------

1) INSTALAR XAMPP
   - Descargar desde: https://www.apachefriends.org/
   - Instalar y abrir el Panel de Control de XAMPP.
   - Iniciar los servicios "Apache" y "MySQL".

2) COPIAR EL PROYECTO
   - Copiar la carpeta "SISClinic" dentro de:
        C:\xampp\htdocs\SISClinic
   - La estructura final debe quedar:
        C:\xampp\htdocs\SISClinic\index.php
        C:\xampp\htdocs\SISClinic\login.php
        C:\xampp\htdocs\SISClinic\modules\...
        C:\xampp\htdocs\SISClinic\includes\...
        C:\xampp\htdocs\SISClinic\css\...
        C:\xampp\htdocs\SISClinic\js\...
        C:\xampp\htdocs\SISClinic\img\...
        C:\xampp\htdocs\SISClinic\database\sisclinic.sql

3) CREAR LA BASE DE DATOS
   - Abrir el navegador y entrar a: http://localhost/phpmyadmin
   - Ir a la pestaña "SQL".
   - Abrir el archivo:
        C:\xampp\htdocs\SISClinic\database\sisclinic.sql
   - Copiar todo su contenido y pegarlo en la pestaña SQL de phpMyAdmin.
   - Presionar "Continuar" o "Ejecutar".
   - Se debe crear automáticamente la base de datos "sisclinic" con
     4 tablas: usuarios, pacientes, evaluaciones y sesiones.
   - Verificar en la barra lateral que aparece "sisclinic".

4) VERIFICAR LA CONEXIÓN
   - Abrir el archivo: C:\xampp\htdocs\SISClinic\conexion.php
   - Confirmar los datos de conexión:
        $servidor   = "localhost";
        $usuario    = "root";
        $contrasena = "";
        $base_datos = "sisclinic";
   - Si tu XAMPP tiene contraseña de root, colócala aquí.

5) INICIAR EL SISTEMA
   - Abrir el navegador y entrar a:
        http://localhost/SISClinic/
   - Si el puerto 80 está ocupado (por ejemplo si usas 8080):
        http://localhost:8080/SISClinic/
   - Debe aparecer la pantalla de inicio de sesión.
   - Si no hay sesión activa, cualquier intento de entrar a una página
     interna redirige automáticamente al login.


--------------------------------------------------------------------------------
CREDENCIALES DE PRUEBA
--------------------------------------------------------------------------------

  Fisioterapeuta:
    Usuario:    fisio1
    Contraseña: Nuevo123

  NOTA: Las contraseñas se almacenan hasheadas con password_hash()
        de PHP y se verifican con password_verify().

  IMPORTANTE: También puedes crear nuevos usuarios desde el módulo
              "Usuarios" (solo disponible para Administradores).


--------------------------------------------------------------------------------
ESTRUCTURA DEL PROYECTO
--------------------------------------------------------------------------------

SISClinic/
│
├── index.php .................... Dashboard principal
├── login.php .................... Inicio de sesión
├── logout.php ................... Cierre de sesión
├── conexion.php ................. Conexión a MySQL (mysqli)
├── README.txt ................... Este archivo
│
├── css/
│   └── styles.css ............... Hoja de estilos principal
│
├── js/
│   └── scripts.js ............... JavaScript del sistema
│
├── img/
│   └── logo.png ................. Logo de SISClinic
│
├── includes/
│   ├── header.php ............... Head + guard de sesión
│   ├── navbar.php ............... Sidebar + topbar
│   └── footer.php ............... Cierre HTML + Chart.js
│
├── modules/
│   ├── pacientes/
│   │   ├── index.php ............ Listado + búsqueda
│   │   ├── registrar.php ........ Registro de pacientes
│   │   ├── editar.php ........... Edición de pacientes
│   │   └── ver.php .............. Expediente con pestañas
│   │
│   ├── evaluaciones/
│   │   ├── index.php ............ Listado + filtros
│   │   ├── registrar.php ........ Registro de evaluaciones
│   │   └── ver.php .............. Detalle de evaluación
│   │
│   ├── sesiones/
│   │   ├── index.php ............ Listado + filtros
│   │   ├── registrar.php ........ Registro de sesiones
│   │   └── ver.php .............. Detalle de sesión
│   │
│   └── usuarios/
│       ├── index.php ............ Listado + toggle estado
│       ├── registrar.php ........ Registro de usuarios
│       ├── editar.php ........... Edición de usuarios
│       └── toggle_estado.php .... Activar/desactivar usuario
│
└── database/
    └── sisclinic.sql ............ Script de base de datos


--------------------------------------------------------------------------------
FUNCIONALIDADES PRINCIPALES
--------------------------------------------------------------------------------

  LOGIN Y SEGURIDAD
    - Inicio de sesión con contraseñas hasheadas (password_hash).
    - Protección de páginas internas (guard de sesión en header.php).
    - Control de acceso por roles (Administrador y Fisioterapeuta).
    - Cierre de sesión manual.

  DASHBOARD
    - Conteos en tiempo real (pacientes, evaluaciones, sesiones,
      profesionales activos).
    - Gráfico de dona: pacientes por sexo.
    - Gráfico de barras: sesiones por mes (últimos 6 meses).
    - Últimos 5 pacientes registrados.
    - Últimas 5 sesiones registradas.
    - Alertas clínicas automáticas:
        · Pacientes sin sesión en más de 30 días.
        · Pacientes con dolor alto en su última sesión.
        · Pacientes sin evaluación registrada.
    - Accesos rápidos a los formularios de registro.

  PACIENTES (CRUD completo)
    - Registrar, listar, buscar, ver, editar.
    - Expediente con pestañas:
        · Datos personales.
        · Información clínica.
        · Evaluaciones.
        · Sesiones.
        · Evolución (gráfico de línea).
    - Registro de próxima sesión tentativa.
    - Registro de sesiones prescritas con barra de progreso.
    - Gráfico de evolución del dolor (Chart.js).

  EVALUACIONES
    - Registrar con escala de dolor 0-10.
    - Campos funcionales: fuerza muscular, rango articular, movilidad,
      equilibrio, marcha.
    - Diagnóstico fisioterapéutico y objetivos del tratamiento.
    - Filtros por paciente, fechas y texto.

  SESIONES
    - Registrar con dolor inicial y final (escala 0-10).
    - Tratamiento, ejercicios/técnicas, respuesta del paciente.
    - Indicador en vivo de reducción/aumento del dolor.
    - Filtros por paciente, fechas y texto.

  USUARIOS (solo Administrador)
    - Registrar, editar, activar/desactivar usuarios.
    - Roles: Administrador y Fisioterapeuta.
    - Auto-protección: no se puede desactivar a sí mismo.
    - Modal de confirmación para cambio de estado.


--------------------------------------------------------------------------------
TECNOLOGÍAS UTILIZADAS
--------------------------------------------------------------------------------

  BACKEND
    - PHP 8.2 (código tradicional organizado por módulos)
    - MySQL (base de datos relacional)
    - mysqli con consultas preparadas (prevención de SQL Injection)

  FRONTEND
    - HTML5
    - CSS3 (con variables CSS y diseño responsivo)
    - Bootstrap 5.3 (interfaz)
    - Bootstrap Icons 1.11
    - JavaScript básico (sliders, modales, interacciones)
    - Chart.js 4.4 (gráficos del dashboard y expediente)

  TIPOGRAFÍA
    - Inter (Google Fonts)

  SERVIDOR LOCAL
    - XAMPP (Apache + MySQL)
    - phpMyAdmin (administración de la BD)


--------------------------------------------------------------------------------
SEGURIDAD IMPLEMENTADA
--------------------------------------------------------------------------------

  - Contraseñas hasheadas con password_hash() y verificadas con
    password_verify().
  - Consultas preparadas (mysqli_prepare) en todas las operaciones
    que reciben datos del usuario.
  - Escape de datos mostrados con htmlspecialchars().
  - Regeneración de ID de sesión tras iniciar sesión
    (session_regenerate_id).
  - Guard de sesión en header.php: redirige al login si no hay
    sesión activa.
  - Guard de rol: el módulo de Usuarios solo es accesible para
    Administradores.
  - Validación doble: HTML5 (cliente) + PHP (servidor).
  - CSRF protección básica mediante formularios POST y confirmación
    visual para acciones destructivas.
  - Auto-protección: un Administrador no puede desactivarse a sí
    mismo ni cambiarse el rol.


--------------------------------------------------------------------------------
COLORES DEL SISTEMA
--------------------------------------------------------------------------------

  Verde esmeralda .......... #57cc99 (acentos, badges success)
  Teal .................... #38a3a5 (primario)
  Azul profundo ........... #22577a (títulos y sidebar)
  Ámbar ................... #c67c3e (alertas clínicas)
  Coral ................... #e76f51 (acentos cálidos)
  Violeta ................. #7c6fbc (acentos informativos)
  Fondo ................... #f3fbf4 (muy claro verdoso)
  Texto principal ......... #1a4459


--------------------------------------------------------------------------------
PALABRAS CLAVE
--------------------------------------------------------------------------------
  Fisioterapia, gestión clínica, expediente electrónico, evaluación
  fisioterapéutica, seguimiento de tratamiento, PHP, MySQL, Bootstrap,
  Chart.js, XAMPP.


--------------------------------------------------------------------------------
NOTAS Y ACLARACIONES
--------------------------------------------------------------------------------

  1. El sistema NO realiza diagnósticos automáticos. El criterio clínico
     siempre corresponde al profesional de fisioterapia.

  2. El sistema NO incluye pagos, facturación, portal de pacientes,
     chat, ni aplicación móvil (por decisión de diseño).

  3. Todos los datos mostrados son ingresados manualmente por el
     profesional autorizado.

  4. La base de datos incluye 3 pacientes de prueba y 1 evaluación
     para facilitar las pruebas iniciales.

  5. Para probar los gráficos de evolución, se recomienda registrar
     al menos 2 sesiones para el mismo paciente.



================================================================================
                             FIN DEL DOCUMENTO
================================================================================
