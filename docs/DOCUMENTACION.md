## ✅ 1. Objetivo del MVP

Crear una aplicación web **PWA mínima pero funcional**, donde los usuarios que compran recetas digitales de tu tienda **Sazón Criollo en Etsy** puedan:

- Iniciar sesión y gestionar su cuenta.
- Visualizar sus recetas en PDF con una interfaz tipo **libro animado**.
- Escuchar las recetas mediante **lectura en voz (text-to-speech)**.
- Recibir sugerencias de nuevos productos disponibles en tu Etsy.
- Navegar un menú básico con opciones de personalización.

Además, incluir un **panel administrativo sencillo** para gestionar clientes y publicar anuncios.

👉 **Objetivo clave:** validar que los clientes valoran una experiencia premium para leer y organizar sus recetas, y que esto aumente las ventas y el engagement.

## ✅ 2. Funcionalidades del Usuario (MVP)

### 🔐 Autenticación
- Registro mediante link generado por admin.
- Inicio de sesión (email + contraseña + fecha de nacimiento).
- Recuperación de contraseña (via PHPMailer).
- Cerrar sesión.

### 📚 Gestión de Recetas
- Subir sus PDFs comprados.
- Lista de recetas con portada o nombre.
- Visualización en visor PDF con:
  - Animación tipo libro (turn.js).
  - Lectura con voz (Web Speech API).
- Borrar receta.

### 🧭 Interfaz y Navegación
- Pantalla de bienvenida (“Agrega tu primera receta”).
- Animación de libros.
- Sugerencias dinámicas de productos:
  - Imagen del producto.
  - Título.
  - Botón → redirección a Etsy mediante URL.

### ⚙️ Menú del Usuario
- Cambio de idioma (ES/EN).
- Cambio de tema (claro/oscuro).
- Foto de perfil.
- Nombre del usuario.
- Correo electrónico.
- Atención al cliente (link a WhatsApp).
- Cambiar contraseña.

## ✅ 3. Funcionalidades del Administrador (MVP)

### 👥 Gestión de usuarios
- Ver lista de clientes registrados.
- Editar datos (nombre, email,).
- Eliminar cliente.
- Generar link temporal (10 minutos) para registro.

### 📰 Gestión de campañas publicitarias - con cupones fechas especiales 
Este módulo se mostrará en la pantalla principal del usuario.

#### Campos:
- Título del anuncio
- Imagen/banner
- URL destino (link al producto o promoción)
- Fecha/hora de inicio
- Fecha/hora de fin

#### Funciones admin:
- Crear campaña
- Editar campaña
- Eliminar campaña
- Activar/desactivar campaña
- Para quien va dirigidos (TODOS, UNO EN ESPECIFICO)


#### Funciones usuario:
- Ver banner en la landing principal
- Clic que redirecciona al producto

## ✅ 4. Flujo del Usuario (UX)

### 🔵 Flujo 1: Registro
1. Admin genera link temporal.
2. Usuario abre link.
3. Rellena formulario (nombre, usuario, email, pin, permisos de email).
4. Es redirigido a login.
5. Inicia sesión.

### 🔵 Flujo 2: Pantalla principal
El usuario entra y ve:
- Sus recetas
- Botón “Agregar receta”
- Banner publicitario
- Sugerencias de Etsy

### 🔵 Flujo 3: Ver una receta
1. Selecciona receta.
2. Se abre visor tipo libro (turn.js).

Puede:
- Pasar páginas con animación
- Activar lectura de voz
- Cambiar idioma

### 🔵 Flujo 4: Menú
Accede al menú lateral o superior.

Puede:
- Editar perfil  
- Cambiar idioma  
- Cambiar apariencia  
- Acceder a soporte  
- Cerrar sesión  

### 🔵 Flujo 5: Recuperar contraseña
1. Usuario ingresa email.
2. PHPMailer envía link de recuperación.
3. Usuario cambia contraseña.

### 🔵 Flujo 6: Flujo de campañas
1. Admin publica campaña.
2. Usuario ve banner en pantalla principal.
3. Usuario hace clic → redirige a Etsy.

