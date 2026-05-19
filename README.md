# Pharmacity FE

Project frontend duoc tao tu template Vue 3 + Vite trong thu muc goc hien tai.

## Scripts

- `npm install`
- `npm run dev`
- `npm run build`

## Ket noi backend

Mac dinh FE goi API qua `/api` va Vite proxy sang Laravel tai `http://127.0.0.1:8000`.

Chay backend:

```bash
cd ../Pharmacity
php artisan serve
```

Chay frontend:

```bash
npm run dev
```

Neu Laravel chay o cong khac, dat `VITE_BACKEND_URL` trong `.env`, vi du:

```env
VITE_BACKEND_URL=http://127.0.0.1:8001
```
