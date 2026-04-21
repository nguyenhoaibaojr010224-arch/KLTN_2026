<template>
  <div ref="root" class="pc-pill-select" :class="{ 'is-open': isOpen }">
    <button
      type="button"
      class="pc-pill-select__trigger"
      :aria-expanded="isOpen ? 'true' : 'false'"
      aria-haspopup="listbox"
      @click="toggleMenu"
    >
      <span class="pc-pill-select__value">{{ selectedLabel }}</span>
      <span class="pc-pill-select__icon">
        <i class="bi bi-chevron-down"></i>
      </span>
    </button>

    <Transition name="pc-pill-select-fade">
      <div v-if="isOpen" class="pc-pill-select__menu" role="listbox">
        <button
          v-for="option in normalizedOptions"
          :key="option.key"
          type="button"
          class="pc-pill-select__option"
          :class="{ active: option.value === modelValue }"
          @click="selectOption(option.value)"
        >
          <span>{{ option.label }}</span>
          <i v-if="option.value === modelValue" class="bi bi-check2"></i>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script>
export default {
  name: 'ProductVariantSelect',

  props: {
    modelValue: {
      type: String,
      default: '',
    },
    options: {
      type: Array,
      default: () => [],
    },
    placeholder: {
      type: String,
      default: 'Chọn phân loại',
    },
  },

  data() {
    return {
      isOpen: false,
    };
  },

  computed: {
    normalizedOptions() {
      return this.options
        .map((option, index) => {
          if (typeof option === 'string') {
            return {
              key: `${option}-${index}`,
              label: option,
              value: option,
            };
          }

          const value = String(option?.ten_don_vi || option?.value || option?.label || '');

          return {
            key: option?.lookup_key || option?.key || `${value}-${index}`,
            label: String(option?.ten_don_vi || option?.label || value),
            value,
          };
        })
        .filter((option) => option.value);
    },

    selectedLabel() {
      return this.normalizedOptions.find((option) => option.value === this.modelValue)?.label || this.placeholder;
    },
  },

  mounted() {
    document.addEventListener('click', this.handleDocumentClick);
    document.addEventListener('keydown', this.handleEscapeKey);
  },

  beforeUnmount() {
    document.removeEventListener('click', this.handleDocumentClick);
    document.removeEventListener('keydown', this.handleEscapeKey);
  },

  methods: {
    toggleMenu() {
      this.isOpen = !this.isOpen;
    },

    selectOption(value) {
      this.$emit('update:modelValue', value);
      this.isOpen = false;
    },

    handleDocumentClick(event) {
      if (!this.isOpen) {
        return;
      }

      if (!this.$refs.root?.contains(event.target)) {
        this.isOpen = false;
      }
    },

    handleEscapeKey(event) {
      if (event.key === 'Escape') {
        this.isOpen = false;
      }
    },
  },
};
</script>

<style scoped>
.pc-pill-select {
  position: relative;
  width: 100%;
  z-index: 5;
}

.pc-pill-select__trigger {
  width: 100%;
  min-height: 58px;
  padding: 0.85rem 1rem 0.85rem 1.35rem;
  border: 1px solid rgba(25, 74, 168, 0.14);
  border-radius: 999px;
  background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 14px 28px rgba(38, 73, 136, 0.08);
  color: #18345b;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  font-size: 1.06rem;
  font-weight: 700;
  text-align: left;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.pc-pill-select__trigger:hover {
  border-color: rgba(22, 82, 197, 0.3);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.95), 0 18px 34px rgba(38, 73, 136, 0.12);
}

.pc-pill-select__trigger:focus-visible {
  outline: none;
  border-color: rgba(22, 82, 197, 0.42);
  box-shadow: 0 0 0 0.24rem rgba(22, 82, 197, 0.14), 0 18px 34px rgba(38, 73, 136, 0.12);
}

.pc-pill-select.is-open .pc-pill-select__trigger {
  border-color: rgba(22, 82, 197, 0.38);
  box-shadow: 0 0 0 0.24rem rgba(22, 82, 197, 0.1), 0 20px 36px rgba(38, 73, 136, 0.14);
}

.pc-pill-select__value {
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pc-pill-select__icon {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(37, 94, 204, 0.08);
  color: #255ecc;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: transform 0.2s ease, background-color 0.2s ease;
}

.pc-pill-select.is-open .pc-pill-select__icon {
  transform: rotate(180deg);
  background: rgba(37, 94, 204, 0.14);
}

.pc-pill-select__menu {
  position: absolute;
  top: calc(100% + 12px);
  left: 0;
  width: 100%;
  padding: 0.6rem;
  border: 1px solid rgba(25, 74, 168, 0.12);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 28px 54px rgba(28, 64, 127, 0.18);
  backdrop-filter: blur(14px);
}

.pc-pill-select__option {
  width: 100%;
  border: none;
  border-radius: 18px;
  background: transparent;
  color: #18345b;
  padding: 0.9rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  font-size: 1rem;
  font-weight: 700;
  text-align: left;
  transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
}

.pc-pill-select__option + .pc-pill-select__option {
  margin-top: 0.3rem;
}

.pc-pill-select__option:hover {
  background: rgba(37, 94, 204, 0.08);
}

.pc-pill-select__option.active {
  background: linear-gradient(135deg, #255ecc 0%, #194aa8 100%);
  color: #ffffff;
  box-shadow: 0 12px 22px rgba(37, 94, 204, 0.22);
}

.pc-pill-select-fade-enter-active,
.pc-pill-select-fade-leave-active {
  transition: opacity 0.16s ease, transform 0.16s ease;
}

.pc-pill-select-fade-enter-from,
.pc-pill-select-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

@media (max-width: 767px) {
  .pc-pill-select__trigger {
    min-height: 54px;
    font-size: 1rem;
  }
}
</style>
