# Subida de Archivos con PHP

Aplicación web para subir imágenes y documentos PDF, y consultar los archivos guardados desde el navegador.

---

##  Objetivo

Aprender a trabajar con formularios HTML y el procesamiento de archivos en PHP mediante el uso de la superglobal `$_FILES`.

---

## Estructura del Proyecto

```text
subir_archivos/
├── index.html
├── guardar.php
└── archivos/

```

* **`index.html`**: Interfaz para elegir o arrastrar archivos, personalizar su nombre, subirlos y abrir los ya guardados.
* **`guardar.php`**: Valida el tipo, el tamaño y el nombre, añade un identificador único, guarda el archivo y entrega la lista.
* **`archivos/`**: Carpeta de destino, creada automáticamente por PHP al guardar el primer archivo.

---

## ¿Cómo funciona?

1. El usuario selecciona o arrastra una imagen o PDF desde `index.html` y puede cambiar el nombre propuesto.
2. El navegador comprueba la extensión y el límite de 10 MB; PHP vuelve a validar el archivo recibido.
3. PHP guarda el archivo con el nombre elegido y un identificador único en `archivos/`.
4. La lista se actualiza y cada nombre permite abrir el archivo guardado.

---

## Recomendaciones para la Ejecución

* **Servidor local:** Inicia Apache en **XAMPP** y abre `http://localhost/subir_archivos/`.
* **Permisos de escritura:** PHP necesita permiso para crear y escribir en la carpeta del proyecto.
