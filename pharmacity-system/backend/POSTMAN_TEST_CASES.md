# Postman Test Cases

## Auth - Public
- `POST /api/login`
  - Success with `admin/password`
  - Success with `staff/password`
  - `422` when missing `ten_dang_nhap`
  - `422` when wrong password

## Thong Tin Nhan Vien - Admin va Nhan Vien
- `POST /api/admin/thong-tin-nhan-viens`
  - `201` create success
  - `422` duplicate email
  - `422` age < 18
  - `403` when staff calls admin route
- `GET /api/admin/thong-tin-nhan-viens/{id}`
  - `200` success
  - `404` not found
- `PUT /api/admin/thong-tin-nhan-viens/{id}`
  - `200` success
  - `422` invalid phone
- `DELETE /api/admin/thong-tin-nhan-viens/{id}`
  - `200` success
  - `404` not found
- `GET /api/thong-tin-nhan-vien/me`
  - `200` success
  - `404` when profile detail does not exist

## Phieu Nhap - Admin va Nhan Vien
- `POST /api/phieu-nhaps`
  - `201` success
  - `422` invalid `id_nha_san_xuat`
  - `403` customer/no token
- `POST /api/phieu-nhaps/{id}/chi-tiets`
  - `201` success
  - `404` phieu nhap not found
  - `422` invalid `id_lo`
- `PUT /api/chi-tiet-phieu-nhaps/{id}`
  - `200` success
  - `422` quantity makes lot invalid
- `DELETE /api/chi-tiet-phieu-nhaps/{id}`
  - `200` success
  - `404` not found
- `GET /api/admin/phieu-nhaps/statistics`
  - `200` success

## Hoa Don Va Chi Tiet - Admin va Nhan Vien
- `POST /api/hoa-dons`
  - `201` success
  - `422` invalid `id_khach_hang`
- `POST /api/hoa-dons/{id}/chi-tiets`
  - `201` success
  - `422` quantity > stock
  - `404` hoa don not found
- `PUT /api/chi-tiet-hoa-dons/{id}`
  - `200` success
  - `422` move to lot without enough stock
- `DELETE /api/chi-tiet-hoa-dons/{id}`
  - `200` success
  - `404` not found

## Thanh Toan - Admin va Nhan Vien
- `POST /api/thanh-toans`
  - `201` success for `tien_mat`
  - `422` `so_tien` not equal invoice payable
  - `422` missing `ma_giao_dich` for `chuyen_khoan`
  - `404` hoa don not found

## Lich Su Don Hang - Admin va Nhan Vien
- `POST /api/hoa-dons/{id}/lich-su`
  - `201` success
  - `422` invalid status
  - `404` hoa don not found

## Email Verification - Public va Khach Hang
- `POST /api/email-verifications/request`
  - `201` success
  - `422` email not in `khach_hangs`
- `GET /api/email-verifications/verify/{token}`
  - `200` success
  - `404` invalid token
  - `422` expired token

## Password Reset - Public va Khach Hang
- `POST /api/password-resets/request`
  - `201` success
  - `422` unknown email
- `POST /api/password-resets/validate-token`
  - `200` success
  - `404` invalid token
- `POST /api/password-resets/reset`
  - `200` success
  - `422` expired token
  - `422` used token
  - `404` wrong email/token pair
