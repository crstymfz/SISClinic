-- SISClinic - Base de datos
-- Sistema interno de gestión de pacientes y seguimiento de fisioterapia


SET NAMES utf8mb4; -- para que tildes y ñ se guarden bien al importar

DROP DATABASE IF EXISTS sisclinic;
CREATE DATABASE sisclinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sisclinic;


-- Tabla: usuarios

CREATE TABLE usuarios (
    id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    usuario         VARCHAR(50)  NOT NULL UNIQUE,
    contrasena      VARCHAR(255) NOT NULL,          -- aquí se guarda el hash de password_hash()
    rol             ENUM('Administrador','Fisioterapeuta') NOT NULL,
    estado          TINYINT(1)   NOT NULL DEFAULT 1 -- 1 = activo, 0 = inactivo
) ENGINE=InnoDB;


-- Tabla: pacientes

CREATE TABLE pacientes (
    id_paciente        INT AUTO_INCREMENT PRIMARY KEY,
    nombres            VARCHAR(100) NOT NULL,
    apellidos          VARCHAR(100) NOT NULL,
    fecha_nacimiento   DATE         NOT NULL,
    sexo               ENUM('Femenino','Masculino') NOT NULL,
    telefono           VARCHAR(20)  NOT NULL,
    correo             VARCHAR(100) NULL,
    direccion          VARCHAR(200) NULL,
    motivo_consulta    TEXT         NOT NULL,
    diagnostico_medico TEXT         NULL,
    antecedentes       TEXT         NULL,
    observaciones      TEXT         NULL,
    fecha_registro     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- Tabla: evaluaciones
-- Un paciente tiene muchas evaluaciones; un fisioterapeuta registra muchas.

CREATE TABLE evaluaciones (
    id_evaluacion                INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente                  INT  NOT NULL,
    id_usuario                   INT  NOT NULL,
    fecha_evaluacion             DATE NOT NULL,
    dolor                        TINYINT NOT NULL,
    fuerza_muscular              VARCHAR(100) NULL,
    rango_articular              VARCHAR(100) NULL,
    movilidad                    VARCHAR(100) NULL,
    equilibrio                   VARCHAR(100) NULL,
    marcha                       VARCHAR(100) NULL,
    observaciones                TEXT NULL,
    diagnostico_fisioterapeutico TEXT NULL,
    objetivos_tratamiento        TEXT NULL,
    CONSTRAINT chk_eval_dolor CHECK (dolor BETWEEN 0 AND 10),
    CONSTRAINT fk_eval_paciente FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_eval_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;


-- Tabla: sesiones
-- Un paciente tiene muchas sesiones; un fisioterapeuta registra muchas.

CREATE TABLE sesiones (
    id_sesion             INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente           INT  NOT NULL,
    id_usuario            INT  NOT NULL,
    fecha                 DATE NOT NULL,
    dolor_inicial         TINYINT NOT NULL,
    tratamiento_realizado TEXT NOT NULL,
    ejercicios_tecnicas   TEXT NULL,
    respuesta_paciente    TEXT NULL,
    dolor_final           TINYINT NOT NULL,
    observaciones         TEXT NULL,
    CONSTRAINT chk_ses_dolor_inicial CHECK (dolor_inicial BETWEEN 0 AND 10),
    CONSTRAINT chk_ses_dolor_final   CHECK (dolor_final   BETWEEN 0 AND 10),
    CONSTRAINT fk_ses_paciente FOREIGN KEY (id_paciente)
        REFERENCES pacientes(id_paciente) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_ses_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;


-- Datos de prueba
-- Contraseñas (ya hasheadas con password_hash de PHP):


INSERT INTO usuarios (nombre_completo, usuario, contrasena, rol, estado) VALUES
('Administrador del Sistema', 'admin',  '$2y$10$nJZ2mCRr/Lx0Qo7GqUMZhOixrAUjYmApU8PBs0QacY3tMEOpj8nZG', 'Administrador', 1),
('Fisioterapeuta de Prueba',  'fisio1', '$2y$10$XAu7KX2SHZIPrgA17l0GfeYqnmrSLXQLstFRT4fFw5FwJ72IWHuWe', 'Fisioterapeuta', 1);

INSERT INTO pacientes (nombres, apellidos, fecha_nacimiento, sexo, telefono, correo, direccion, motivo_consulta, diagnostico_medico, antecedentes, observaciones) VALUES
('José Luis', 'Ramírez Torres', '1979-03-14', 'Masculino', '8888-1234', 'jose.ramirez@example.com', 'Managua, Barrio Monseñor Lezcano',
 'Dolor lumbar persistente al estar sentado', 'Lumbalgia crónica', 'Trabajo de oficina, sedentarismo', NULL),
('Ana María', 'Martínez López', '1993-07-22', 'Femenino', '8777-5678', NULL, 'Managua, Altamira',
 'Molestia y limitación tras torcedura de tobillo', 'Esguince de tobillo grado II', 'Sin antecedentes relevantes', 'Refiere caída hace dos semanas'),
('Carlos', 'Mendoza Ruiz', '1958-11-05', 'Masculino', '8666-9012', NULL, 'Masaya',
 'Rigidez y dolor en rodilla derecha', 'Gonartrosis leve', 'Hipertensión controlada', NULL);

INSERT INTO evaluaciones (id_paciente, id_usuario, fecha_evaluacion, dolor, fuerza_muscular, rango_articular, movilidad, equilibrio, marcha, observaciones, diagnostico_fisioterapeutico, objetivos_tratamiento) VALUES
(1, 2, CURDATE(), 6, 'Disminuida en zona lumbar', 'Flexión lumbar limitada', 'Movilidad reducida', 'Sin alteraciones', 'Normal',
 'Dolor aumenta al final de la jornada', 'Dolor lumbar mecánico con acortamiento de cadena posterior',
 'Reducir dolor y mejorar movilidad lumbar');

INSERT INTO sesiones (id_paciente, id_usuario, fecha, dolor_inicial, tratamiento_realizado, ejercicios_tecnicas, respuesta_paciente, dolor_final, observaciones) VALUES
(1, 2, CURDATE(), 6, 'Terapia manual y calor local', 'Estiramientos de cadena posterior', 'Buena tolerancia', 4, 'Continuar con ejercicios en casa'),
(2, 2, CURDATE(), 5, 'Crioterapia y ejercicios de movilidad de tobillo', 'Círculos de tobillo, propiocepción básica', 'Refiere alivio', 3, NULL);
