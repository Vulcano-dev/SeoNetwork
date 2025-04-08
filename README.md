# 📄 Aplicación Web de Gestión de Páginas y Etiquetas

Una completa aplicación web para crear, organizar y etiquetar páginas, ideal para proyectos donde se requiere estructurar contenido dinámico.

---

## 📋 Requisitos

Antes de instalar la aplicación, asegúrate de contar con lo siguiente:

- PHP 7.4 o superior
- Composer (https://getcomposer.org/)
- Servidor Web (Apache o Nginx)
- MySQL o MariaDB
- Navegador web moderno
- Acceso a la terminal / consola (opcional para instalación avanzada)

---

## 🧰 Instalación paso a paso

### 1. Clona o descarga este repositorio

```bash
git clone https://github.com/tuusuario/gestor-paginas-etiquetas.git
cd gestor-paginas-etiquetas
```

> Alternativamente, puedes subir los archivos a tu hosting manualmente mediante FTP.

---

### 2. Instala dependencias con Composer

```bash
composer install
```

Si no tienes Composer instalado, puedes hacerlo con:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

---

### 3. Configura la base de datos

1. Crea una base de datos nueva en MySQL.
2. Importa la estructura inicial con el siguiente SQL:

```sql
CREATE TABLE paginas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255),
  url VARCHAR(255),
  titulo VARCHAR(255),
  padre_id INT DEFAULT NULL
);

CREATE TABLE etiquetas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  etiqueta VARCHAR(255),
  estado VARCHAR(20)
);

CREATE TABLE pagina_etiqueta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pagina_id INT,
  etiqueta_id INT
);
```

3. Abre el archivo `db_config.php` y edita tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'nombre_base_datos');
define('DB_USER', 'usuario');
define('DB_PASS', 'contraseña');
```

---

### 4. Configura UTF-8 en tu entorno

Asegúrate de que tu archivo `php.ini` tenga estas líneas:

```ini
default_charset = "UTF-8"
```

---

## ▶️ Ejecución

Sube los archivos a tu servidor web o ejecútalo en local accediendo con tu navegador a `index.php`.

Desde ahí podrás:

- Ver el listado de páginas y asociarlas a etiquetas.
- Crear nuevas páginas o etiquetas mediante modales.
- Usar el menú flotante para una navegación rápida.
- Filtrar y buscar en tiempo real.
- Importar etiquetas desde Excel.

---

## ⚠️ Problemas conocidos

- Si los caracteres especiales (acentos, ñ) no se muestran correctamente, revisa la configuración UTF-8.
- Asegúrate de tener permisos adecuados en los archivos si estás en un servidor Linux.
- Composer debe estar instalado globalmente o en la carpeta del proyecto.

---

## 📄 Licencia

MIT License. Uso libre y gratuito.
