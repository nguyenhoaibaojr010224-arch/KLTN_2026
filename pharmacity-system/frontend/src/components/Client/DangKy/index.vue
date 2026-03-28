<template>
  <section class="register-shell">
    <div class="register-card">
      <div class="register-card__hero">
        <div class="soft-badge soft-badge--teal mb-3">
          <i class="bi bi-person-plus"></i>
          Tạo tài khoản mới
        </div>
        <h1 class="register-card__title">Tạo tài khoản khách hàng</h1>
        <p class="register-card__copy">
          Đăng ký để mua thuốc, lưu thông tin giao hàng và theo dõi ưu đãi nhanh hơn trên hệ thống.
        </p>
      </div>

      <div class="register-card__form">
        <div class="register-card__intro mb-4">
          <h2 class="h4 fw-bold mb-2">Thông tin đăng ký</h2>
          <p class="text-secondary mb-0">
            Điền đầy đủ thông tin bên dưới để tạo tài khoản mới.
          </p>
        </div>

        <form class="row g-3" @submit.prevent="dangKy">
          <div class="col-12">
            <label class="form-label fw-semibold">Họ và tên</label>
            <input
              v-model.trim="form.ten_khach_hang"
              class="form-control form-control-lg"
              :class="{ 'is-invalid': fieldErrors.ten_khach_hang }"
              @blur="handleBlur('ten_khach_hang')"
            >
            <div v-if="fieldErrors.ten_khach_hang" class="field-error">
              {{ fieldErrors.ten_khach_hang }}
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Số điện thoại</label>
            <input
              v-model.trim="form.so_dien_thoai"
              class="form-control form-control-lg"
              :class="{ 'is-invalid': fieldErrors.so_dien_thoai }"
              @blur="handleBlur('so_dien_thoai')"
            >
            <div v-if="fieldErrors.so_dien_thoai" class="field-error">
              {{ fieldErrors.so_dien_thoai }}
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input
              v-model.trim="form.email"
              class="form-control form-control-lg"
              :class="{ 'is-invalid': fieldErrors.email }"
              @blur="handleBlur('email')"
            >
            <div v-if="fieldErrors.email" class="field-error">
              {{ fieldErrors.email }}
            </div>
          </div>

          <div class="col-12">
            <label class="form-label fw-semibold">Địa chỉ</label>
            <input
              v-model.trim="form.dia_chi"
              class="form-control form-control-lg"
              :class="{ 'is-invalid': fieldErrors.dia_chi }"
              @blur="handleBlur('dia_chi')"
            >
            <div v-if="fieldErrors.dia_chi" class="field-error">
              {{ fieldErrors.dia_chi }}
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Mật khẩu</label>
            <input
              v-model="form.mat_khau"
              type="password"
              class="form-control form-control-lg"
              :class="{ 'is-invalid': fieldErrors.mat_khau }"
              @blur="handleBlur('mat_khau')"
            >
            <div v-if="fieldErrors.mat_khau" class="field-error">
              {{ fieldErrors.mat_khau }}
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold">Xác nhận mật khẩu</label>
            <input
              v-model="form.mat_khau_confirmation"
              type="password"
              class="form-control form-control-lg"
              :class="{ 'is-invalid': fieldErrors.mat_khau_confirmation }"
              @blur="handleBlur('mat_khau_confirmation')"
            >
            <div v-if="fieldErrors.mat_khau_confirmation" class="field-error">
              {{ fieldErrors.mat_khau_confirmation }}
            </div>
          </div>

          <div class="col-12 register-card__actions">
            <button class="btn btn-primary btn-lg register-card__submit" type="submit" :disabled="loading">
              <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
              Tạo tài khoản
            </button>
          </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
          <RouterLink to="/login" class="text-decoration-none fw-semibold text-primary">
            Đã có tài khoản? Đăng nhập ngay
          </RouterLink>
          <RouterLink to="/" class="text-decoration-none text-secondary">Quay về trang chủ</RouterLink>
        </div>

        <div v-if="message" class="alert alert-info mt-4 mb-0">{{ message }}</div>
        <div v-if="formError" class="alert alert-danger mt-3 mb-0">{{ formError }}</div>
      </div>
    </div>
  </section>
</template>

<script>
import { register } from "../../../api/authApi";
import { setAuthSession } from "../../../lib/authStorage";

export default {
  data() {
    return {
      loading: false,
      formError: "",
      message: "",
      form: {
        ten_khach_hang: "",
        so_dien_thoai: "",
        email: "",
        dia_chi: "",
        mat_khau: "",
        mat_khau_confirmation: "",
      },
      fieldErrors: {
        ten_khach_hang: "",
        so_dien_thoai: "",
        email: "",
        dia_chi: "",
        mat_khau: "",
        mat_khau_confirmation: "",
      },
      touchedFields: {
        ten_khach_hang: false,
        so_dien_thoai: false,
        email: false,
        dia_chi: false,
        mat_khau: false,
        mat_khau_confirmation: false,
      },
    };
  },
  methods: {
    translateServerMessage(message) {
      if (!message) return "Dữ liệu chưa hợp lệ.";

      const normalized = String(message).toLowerCase();

      if (normalized.includes("required")) return "Trường này là bắt buộc.";
      if (normalized.includes(".com")) return "Email phải đúng định dạng.";
      if (normalized.includes("already been taken")) return "Dữ liệu này đã tồn tại.";
      if (normalized.includes("must be at least")) return "Giá trị nhập vào chưa đủ độ dài yêu cầu.";
      if (normalized.includes("confirmation does not match")) return "Xác nhận mật khẩu không khớp.";
      if (normalized.includes("must be a string")) return "Giá trị nhập vào chưa đúng định dạng.";
      if (normalized.includes("regex")) {
        return "Mật khẩu phải có ít nhất 8 ký tự, gồm 1 chữ in hoa và 1 ký tự đặc biệt.";
      }

      return message;
    },
    validateField(field) {
      const value = String(this.form[field] || "").trim();

      switch (field) {
        case "ten_khach_hang":
          if (!value) return "Vui lòng nhập họ và tên.";
          if (value.length < 3) return "Họ và tên phải có ít nhất 3 ký tự.";
          return "";
        case "so_dien_thoai":
          if (!value) return "Vui lòng nhập số điện thoại.";
          if (!/^\d{10}$/.test(value)) return "Số điện thoại phải gồm đúng 10 chữ số.";
          return "";
        case "email":
          if (!value) return "Vui lòng nhập email.";
          if (!/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.com$/.test(value)) {
            return "Email phải đúng định dạng.";
          }
          return "";
        case "dia_chi":
          if (!value) return "Vui lòng nhập địa chỉ.";
          if (value.length < 5) return "Địa chỉ phải có ít nhất 5 ký tự.";
          return "";
        case "mat_khau":
          if (!this.form.mat_khau) return "Vui lòng nhập mật khẩu.";
          if (this.form.mat_khau.length < 8) return "Mật khẩu phải có ít nhất 8 ký tự.";
          if (!/[A-Z]/.test(this.form.mat_khau)) return "Mật khẩu phải có ít nhất 1 chữ in hoa.";
          if (!/[!@#$%^&*(),.?\":{}|<>_\-[\]\\\/+=~`';]/.test(this.form.mat_khau)) {
            return "Mật khẩu phải có ít nhất 1 ký tự đặc biệt.";
          }
          return "";
        case "mat_khau_confirmation":
          if (!this.form.mat_khau_confirmation) return "Vui lòng xác nhận mật khẩu.";
          if (this.form.mat_khau_confirmation !== this.form.mat_khau) return "Xác nhận mật khẩu không khớp.";
          return "";
        default:
          return "";
      }
    },
    validateForm(markTouched = false) {
      let isValid = true;

      Object.keys(this.fieldErrors).forEach((field) => {
        if (markTouched) {
          this.touchedFields[field] = true;
        }

        const nextError = this.validateField(field);
        this.fieldErrors[field] = this.touchedFields[field] ? nextError : "";

        if (nextError) {
          isValid = false;
        }
      });

      return isValid;
    },
    handleBlur(field) {
      this.touchedFields[field] = true;
      this.fieldErrors[field] = this.validateField(field);
    },
    applyServerErrors(errors) {
      Object.keys(this.fieldErrors).forEach((field) => {
        if (errors?.[field]?.length) {
          this.touchedFields[field] = true;
          this.fieldErrors[field] = this.translateServerMessage(errors[field][0]);
        }
      });
    },
    async dangKy() {
      if (!this.validateForm(true)) {
        this.formError = "Vui lòng kiểm tra lại các trường thông tin bên dưới.";
        return;
      }

      this.loading = true;
      this.formError = "";
      this.message = "";

      try {
        const response = await register(this.form);

        setAuthSession({
          token: response.token,
          user: response.user,
          type: response.type,
        });

        this.message = response.message || "Đăng ký thành công.";
        this.$router.push("/");
      } catch (err) {
        if (err?.payload?.errors) {
          this.applyServerErrors(err.payload.errors);
          this.formError = "Thông tin đăng ký chưa hợp lệ. Vui lòng kiểm tra lại.";
        } else {
          this.formError = this.translateServerMessage(err?.message) || "Đăng ký thất bại.";
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.register-shell {
  width: min(1120px, 100%);
}

.register-card {
  padding: 28px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 32px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(235, 250, 255, 0.96));
  box-shadow: 0 24px 60px rgba(17, 44, 99, 0.08);
}

.register-card__hero {
  max-width: 760px;
  margin-bottom: 28px;
}

.register-card__title {
  margin: 0;
  color: #1f2937;
  font-size: clamp(2.35rem, 4vw, 3.25rem);
  font-weight: 800;
  line-height: 1.08;
}

.register-card__copy {
  max-width: 62ch;
  margin: 14px 0 0;
  color: #475569;
  font-size: 1.1rem;
}

.register-card__form {
  padding: 28px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.76);
  backdrop-filter: blur(10px);
}

.register-card__intro {
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(22, 82, 197, 0.08);
}

.register-card__actions {
  padding-top: 8px;
}

.register-card__submit {
  min-width: 220px;
  border-radius: 18px;
}

.form-control {
  border-radius: 18px;
  border-color: rgba(148, 163, 184, 0.32);
  box-shadow: none;
}

.form-control:focus {
  border-color: rgba(22, 82, 197, 0.4);
  box-shadow: 0 0 0 0.22rem rgba(22, 82, 197, 0.12);
}

.form-control.is-invalid {
  border-color: #dc3545;
  background-image: none;
}

.form-control.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.22rem rgba(220, 53, 69, 0.12);
}

.field-error {
  margin-top: 6px;
  color: #dc3545;
  font-size: 0.92rem;
  font-weight: 500;
}

@media (max-width: 767.98px) {
  .register-card {
    padding: 20px;
    border-radius: 24px;
  }

  .register-card__form {
    padding: 20px;
    border-radius: 22px;
  }

  .register-card__submit {
    width: 100%;
  }
}
</style>
