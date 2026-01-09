# API — Sazón Criollo (MVP)

## Endpoints

### `GET /ping`
- No requiere autenticación.
- Verifica si el API está activo.
- Response: `{"status":"ok","message":"pong"}`

---

### `POST /auth/login`
- Body (JSON): `{"email":"correo","password":"contraseña"}`
- Devuelve token JWT.

---

### `POST /auth/register`
- Body (JSON): `{"token":"temporal","email":"correo","password":"contraseña","nombre":"cliente"}`
- Token generado por el dueño.
- Dura 10 minutos.

---

### `GET /users/profile`
- Header: `Authorization: Bearer <JWT>`
- Devuelve datos del usuario.

---

### `POST /recipes/upload`
- Header: `Authorization: Bearer <JWT>`
- Form-Data:
  - `pdf` (requerido, archivo PDF)
  - `titulo` (opcional)
  - `descripcion` (opcional)
- Guarda PDF y crea receta.
- Response: `{"status":"ok","id_receta":1,"pdf_url":"URL"}`

---

### `GET /recipes/list`
- Header: `Authorization: Bearer <JWT>`
- Lista recetas del cliente ordenadas por fecha.

---

### `GET /recipes/view`
- Header: `Authorization: Bearer <JWT>`
- Param: `id_receta=<number>`
- Devuelve 1 receta.

---

### `PUT /recipes/edit`
- Header: `Authorization: Bearer <JWT>`
- Body (JSON): `{"id_receta":1,"titulo":"nuevo","descripcion":"nuevo"}`
- No edita PDF.

---

### `DELETE /recipes/delete`
- Header: `Authorization: Bearer <JWT>`
- Param: `id_receta=<number>`
- Borra receta y PDF.

---

### `POST /admin/create-registration-link`
- Header: `X-ADMIN-KEY: <clave_admin>`
- Genera link de registro por 10 minutos.
- Response: `{"status":"ok","url":"link","expires_at":"timestamp"}`

---

### `POST /admin/campaigns`
- Header: `X-ADMIN-KEY: <clave_admin>`
- Form-Data:
  - `banner` (requerido, imagen)
  - `device` = `desktop | tablet | mobile`
  - `titulo` (requerido)
  - `descripcion` (opcional)
  - `fecha_final` (opcional, timestamp)
  - `dirigido` (opcional, ID usuario, vacío = pública)
- Crea campaña pública o privada.
- No sobrescribe banners.
- Response: `{"status":"ok","message":"creado"}`

---

### `GET /admin/campaigns/list`
- Header: `X-ADMIN-KEY: <clave_admin>`
- Lista campañas activas e inactivas.

---

### `GET /admin/campaigns/view`
- Header: `X-ADMIN-KEY: <clave_admin>`
- Param: `id_campaña=<number>`
- Devuelve 1 campaña.

---

### `POST /admin/campaigns/edit`
- Header: `X-ADMIN-KEY: <clave_admin>`
- Form-Data opcionales: `id_campaña`, `device`, `banner`, `titulo`, `descripcion`, `fecha_final`, `dirigido`
- Si se envía banner nuevo, se sube como nuevo archivo.

---

### `DELETE /admin/campaigns/deactivate`
- Header: `X-ADMIN-KEY: <clave_admin>`
- Param: `id_campaña=<number>`
- Marca desactivada.

---

### `DELETE /admin/campaigns/delete`
- Header: `X-ADMIN-KEY: <clave_admin>`
- Param: `id_campaña=<number>`
- Borra campaña y banner asociado.

---

## Buckets utilizados
- `recipes` → PDFs de recetas.
- `campaigns` → Banners por dispositivo.
- `avatars` → Fotos de perfil.

---

## Limitaciones del MVP
- Sin roles ni permisos.
- Sin recetas públicas.
- Sin audio.
- Pagos por Etsy.
- Base de datos en Supabase.

---

## Reglas del negocio
- Registro solo con link temporal generado por el dueño.
- Token temporal solo para register y dura 10 min.
- Campañas pueden ser públicas o dirigidas.
- Archivos no se sobrescriben y se guardan con nombre seguro.
