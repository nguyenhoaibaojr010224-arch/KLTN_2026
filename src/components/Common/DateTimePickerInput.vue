<template>
  <div class="date-picker-input">
    <input
      :value="modelValue"
      type="text"
      inputmode="numeric"
      maxlength="16"
      class="form-control"
      :class="[inputClass, { 'is-invalid': invalid }]"
      :placeholder="placeholder"
      :aria-label="ariaLabel"
      @input="handleTextInput"
      @change="emitChange"
    />
    <button type="button" class="date-picker-input__button" aria-label="Mở lịch" @click="openPicker">
      <i class="bi bi-calendar3"></i>
    </button>
    <input
      ref="pickerEl"
      :value="nativeValue"
      type="datetime-local"
      class="date-picker-input__native"
      tabindex="-1"
      aria-hidden="true"
      @change="handlePickerChange"
    />
  </div>
</template>

<script>
import { formatDateTimeInputValue, formatDateTimeTyping, parseDateTimeInputValue } from "../../lib/dateInput";

export default {
  name: "DateTimePickerInput",
  props: {
    modelValue: {
      type: String,
      default: "",
    },
    placeholder: {
      type: String,
      default: "dd/mm/yyyy HH:mm",
    },
    ariaLabel: {
      type: String,
      default: "",
    },
    inputClass: {
      type: [String, Array, Object],
      default: "",
    },
    invalid: {
      type: Boolean,
      default: false,
    },
  },
  emits: ["update:modelValue", "input", "change"],
  computed: {
    nativeValue() {
      return parseDateTimeInputValue(this.modelValue);
    },
  },
  methods: {
    handleTextInput(event) {
      const value = formatDateTimeTyping(event?.target?.value);
      this.$emit("update:modelValue", value);
      this.$emit("input", value);
    },
    handlePickerChange(event) {
      const value = formatDateTimeInputValue(event?.target?.value);
      this.$emit("update:modelValue", value);
      this.$emit("input", value);
      this.$emit("change", value);
    },
    emitChange() {
      this.$emit("change", this.modelValue);
    },
    openPicker() {
      const picker = this.$refs.pickerEl;

      if (!picker) {
        return;
      }

      if (typeof picker.showPicker === "function") {
        picker.showPicker();
        return;
      }

      picker.focus();
      picker.click();
    },
  },
};
</script>

<style scoped>
.date-picker-input {
  position: relative;
}

.date-picker-input :deep(.form-control) {
  padding-right: 2.75rem;
}

.date-picker-input__button {
  position: absolute;
  top: 1px;
  right: 1px;
  bottom: 1px;
  width: 2.5rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-left: 1px solid #dde4ee;
  border-radius: 0 0.375rem 0.375rem 0;
  background: #fff;
  color: #111827;
}

.date-picker-input__button:hover {
  color: #0d6efd;
}

.date-picker-input__native {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}
</style>
