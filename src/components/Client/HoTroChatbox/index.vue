<template>
  <div v-if="canUseSupportChat" class="pc-support-chat">
    <transition name="pc-support-chat-float">
      <section v-if="panelOpen" class="pc-support-chat__panel">
        <header class="pc-support-chat__head">
          <div>
            <strong>Hỗ trợ trực tuyến</strong>
            <p>{{ statusLabel }}</p>
          </div>

          <button type="button" class="pc-support-chat__close" @click="panelOpen = false" aria-label="Đóng khung chat">
            <i class="bi bi-x-lg"></i>
          </button>
        </header>

        <div ref="messagesRef" class="pc-support-chat__messages">
          <div v-if="loading && !conversation" class="pc-support-chat__loading">
            <div class="spinner-border text-primary" role="status"></div>
            <span>Đang tải hội thoại hỗ trợ...</span>
          </div>

          <template v-else-if="conversation?.messages?.length">
            <article
              v-for="message in conversation.messages"
              :key="message.id_tin_nhan"
              class="pc-support-chat__message"
              :class="{
                'is-customer': message.nguoi_gui_loai === 'customer',
                'is-staff': message.nguoi_gui_loai === 'staff',
                'is-ai': message.nguoi_gui_loai === 'ai',
              }"
            >
              <div class="pc-support-chat__bubble">
                <strong>{{ resolveSenderLabel(message) }}</strong>
                <div v-if="resolveIntentLabel(message)" class="pc-support-chat__intent">
                  {{ resolveIntentLabel(message) }}
                </div>
                <p>{{ message.noi_dung }}</p>
                <div v-if="suggestedProducts(message).length" class="pc-support-chat__suggestions">
                  <article
                    v-for="product in suggestedProducts(message)"
                    :key="`${message.id_tin_nhan}-${product.ma_thuoc || product.ten_thuoc}`"
                    class="pc-support-chat__suggestion"
                  >
                    <div class="pc-support-chat__suggestion-thumb">
                      <span v-if="hasRealPromotion(product)" class="pc-support-chat__suggestion-promo">
                        {{ promotionLabel(product) }}
                      </span>
                      <img
                        v-if="product.hinh_anh_url"
                        :src="product.hinh_anh_url"
                        :alt="product.ten_thuoc"
                      />
                      <i v-else class="bi bi-capsule"></i>
                    </div>

                    <div class="pc-support-chat__suggestion-body">
                      <div class="pc-support-chat__suggestion-top">
                        <strong>{{ product.ten_thuoc }}</strong>
                        <span v-if="product.la_thuoc_ke_don" class="pc-support-chat__suggestion-flag">
                          Kê đơn
                        </span>
                      </div>
                      <p v-if="product.nhan">{{ product.nhan }}</p>
                      <div class="pc-support-chat__suggestion-meta">
                        <span class="pc-support-chat__suggestion-price">
                          <span>{{ formatCurrency(product.gia_ban) }}/{{ product.don_vi_hien_thi || "đơn vị" }}</span>
                          <del v-if="hasRealPromotion(product)">{{ formatCurrency(product.gia_niem_yet) }}</del>
                        </span>
                        <span>Còn {{ product.so_luong_ton || 0 }} {{ product.don_vi_ton || product.don_vi_hien_thi || "đơn vị" }}</span>
                      </div>
                      <div class="pc-support-chat__suggestion-actions">
                        <button type="button" @click="openSuggestedProduct(product)">Xem chi tiết</button>
                      </div>
                    </div>
                  </article>
                </div>
                <div v-if="purchaseAction(message)" class="pc-support-chat__purchase-action">
                  <button
                    type="button"
                    class="pc-support-chat__purchase-button"
                    @click="proceedToCheckout(message)"
                  >
                    Thêm vào giỏ và thanh toán
                  </button>
                </div>
                <small>{{ formatDateTime(message.thoi_gian) }}</small>
              </div>
            </article>
          </template>

          <article v-else class="pc-support-chat__message is-ai">
            <div class="pc-support-chat__bubble">
              <strong>Trợ lý AI</strong>
              <div class="pc-support-chat__intent">Tra cứu thuốc</div>
              <p>Khách hàng muốn tìm kiếm sản phẩm nào ạ?</p>
            </div>
          </article>
        </div>

        <div v-if="conversation?.nhan_vien_phu_trach" class="pc-support-chat__assigned">
          Đang hỗ trợ: {{ conversation.nhan_vien_phu_trach.ho_ten }}
        </div>

        <div class="pc-support-chat__quick-actions">
          <button
            v-for="action in quickActions"
            :key="action.label"
            type="button"
            class="pc-support-chat__quick-action"
            :disabled="sending"
            @click="sendQuickAction(action.message)"
          >
            {{ action.label }}
          </button>
        </div>

        <form class="pc-support-chat__composer" @submit.prevent="sendMessage">
          <textarea
            v-model.trim="draftMessage"
            rows="2"
            placeholder="Nhập nội dung cần hỗ trợ..."
            :disabled="sending"
            @keydown.enter.exact.prevent="sendMessage()"
          ></textarea>

          <button type="submit" class="btn btn-primary" :disabled="sending || !draftMessage">
            <span v-if="sending" class="spinner-border spinner-border-sm me-2"></span>
            Gửi
          </button>
        </form>
      </section>
    </transition>

    <button type="button" class="pc-support-chat__toggle" @click="togglePanel">
      <i class="bi bi-chat-dots-fill"></i>
      <span>Chat hỗ trợ</span>
      <span v-if="unreadCount" class="pc-support-chat__badge">{{ unreadCount }}</span>
    </button>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import {
  disconnectGuestSupportConversation,
  getCustomerSupportConversation,
  sendCustomerSupportMessage,
} from "../../../api/supportApi";
import { getAuthType, isAuthenticatedState } from "../../../lib/authStorage";
import { useCustomerStore } from "../../../lib/customerStore";
import { normalizeApiError } from "../../../lib/errorMessages";
import { SUPPORT_CHAT_OPEN_EVENT } from "../../../lib/supportChatEvents";
import { showToast } from "../../../lib/toast";

const panelOpen = ref(false);
const loading = ref(false);
const sending = ref(false);
const conversation = ref(null);
const draftMessage = ref("");
const messagesRef = ref(null);
const router = useRouter();
const customerStore = useCustomerStore();

const quickActions = [
  { label: "Thuốc hạ sốt", message: "Gợi ý cho tôi thuốc hạ sốt" },
  { label: "Khuyến mãi", message: "Đang có khuyến mãi nào nổi bật?" },
  { label: "Đơn hàng", message: "Tôi muốn kiểm tra đơn hàng của tôi" },
  { label: "Tư vấn dược sĩ", message: "Tôi cần tư vấn dược sĩ" },
];

let pollTimer = null;

function normalizeError(error, fallback = "Không thể xử lý hội thoại hỗ trợ.") {
  return normalizeApiError(error, fallback, {
    noi_dung: "nội dung tin nhắn",
    message: "nội dung tin nhắn",
  });
}

const canUseSupportChat = computed(() => !isAuthenticatedState.value || getAuthType() === "customer");
const unreadCount = computed(() => Number(conversation.value?.so_tin_chua_doc || 0));
const statusLabel = computed(() => {
  const status = conversation.value?.trang_thai;
  const latestSenderType =
    conversation.value?.tin_nhan_cuoi?.nguoi_gui_loai
    || conversation.value?.messages?.[conversation.value?.messages?.length - 1]?.nguoi_gui_loai;

  if (latestSenderType === "ai" && status === "moi") {
    return "Trợ lý AI đã chuyển sang dược sĩ";
  }

  if (latestSenderType === "ai") {
    return "Trợ lý AI đang hỗ trợ bước đầu";
  }

  if (status === "dang_trao_doi") {
    return "Nhân viên đang phản hồi";
  }

  if (status === "da_dong") {
    return "Phiên chat đã đóng, bạn có thể gửi tin nhắn mới";
  }

  return "Nhân viên sẽ phản hồi trong ít phút";
});

onMounted(() => {
  window.addEventListener(SUPPORT_CHAT_OPEN_EVENT, handleOpenSupportChat);
  window.addEventListener("pagehide", handleGuestPageExit);
  window.addEventListener("beforeunload", handleGuestPageExit);

  if (canUseSupportChat.value) {
    loadConversation({ silent: true, includeMessages: false });
    startPolling();
  }
});

onBeforeUnmount(() => {
  window.removeEventListener(SUPPORT_CHAT_OPEN_EVENT, handleOpenSupportChat);
  window.removeEventListener("pagehide", handleGuestPageExit);
  window.removeEventListener("beforeunload", handleGuestPageExit);
  stopPolling();
});

watch(canUseSupportChat, async (value) => {
  stopPolling();
  conversation.value = null;
  panelOpen.value = false;

  if (value) {
    await loadConversation({ silent: true, includeMessages: false });
    startPolling();
  }
});

watch(
  () => conversation.value?.messages?.length,
  async () => {
    if (!panelOpen.value) {
      return;
    }

    await nextTick();
    scrollToBottom();
  }
);

async function togglePanel() {
  panelOpen.value = !panelOpen.value;

  if (panelOpen.value) {
    await loadConversation({
      silent: false,
      includeMessages: true,
      markRead: true,
    });
    await nextTick();
    scrollToBottom();
  }
}

async function handleOpenSupportChat(event) {
  if (!canUseSupportChat.value) {
    return;
  }

  const nextMessage = String(event?.detail?.message || "").trim();
  panelOpen.value = true;

  if (nextMessage) {
    draftMessage.value = nextMessage;
  }

  await loadConversation({
    silent: false,
    includeMessages: true,
    markRead: true,
  });
  await nextTick();
  scrollToBottom();
}

async function loadConversation({
  silent = false,
  includeMessages = false,
  markRead = false,
} = {}) {
  if (!canUseSupportChat.value) {
    return;
  }

  if (!silent) {
    loading.value = true;
  }

  try {
    const response = await getCustomerSupportConversation({
      include_messages: includeMessages ? 1 : 0,
      mark_read: markRead ? 1 : 0,
    });

    conversation.value = response?.data || null;
  } catch (error) {
    if (!silent) {
      showToast(normalizeError(error, "Không thể tải hội thoại hỗ trợ."), "error");
    }
  } finally {
    if (!silent) {
      loading.value = false;
    }
  }
}

async function sendMessage(overrideMessage = "") {
  const messageSource =
    overrideMessage && typeof overrideMessage === "object" && "type" in overrideMessage
      ? ""
      : overrideMessage;
  const nextMessage = String(messageSource || draftMessage.value || "").trim();

  if (!nextMessage || sending.value) {
    return;
  }

  sending.value = true;

  try {
    const response = await sendCustomerSupportMessage(nextMessage);
    conversation.value = response?.data || null;
    draftMessage.value = "";
    showToast("Đã gửi tin nhắn hỗ trợ.");
    await nextTick();
    scrollToBottom();
  } catch (error) {
    showToast(normalizeError(error, "Không thể gửi tin nhắn hỗ trợ."), "error");
  } finally {
    sending.value = false;
  }
}

function handleGuestPageExit() {
  if (isAuthenticatedState.value) {
    return;
  }

  disconnectGuestSupportConversation();
}

function sendQuickAction(message) {
  draftMessage.value = message;
  sendMessage(message);
}

function resolveSenderLabel(message) {
  if (message.nguoi_gui_loai === "ai") {
    return "Trợ lý AI";
  }

  return senderLabel(message);
}

function suggestedProducts(message) {
  return Array.isArray(message?.du_lieu_bo_sung?.san_pham_goi_y)
    ? message.du_lieu_bo_sung.san_pham_goi_y
    : [];
}

function hasRealPromotion(product) {
  const salePrice = Number(product?.gia_ban || 0);
  const listPrice = Number(product?.gia_niem_yet || 0);

  return Boolean(product?.co_khuyen_mai) && salePrice > 0 && listPrice > salePrice;
}

function promotionLabel(product) {
  return product?.khuyen_mai_nhan_hien_thi || "Đang ưu đãi";
}

function purchaseAction(message) {
  const action = message?.du_lieu_bo_sung?.hanh_dong_mua_hang;

  return action?.loai === "tao_gio_va_thanh_toan" && action?.san_pham ? action : null;
}

function resolveIntentLabel(message) {
  const intent = message?.du_lieu_bo_sung?.intent;

  if (intent === "tu_van_duoc_si") {
    return "Tư vấn dược sĩ";
  }

  if (intent === "khuyen_mai") {
    return "Khuyến mãi";
  }

  if (intent === "don_hang") {
    return "Đơn hàng";
  }

  if (intent === "dat_hang") {
    return "Đặt hàng";
  }

  if (intent === "tra_cuu_thuoc") {
    return "Tra cứu thuốc";
  }

  return "";
}

function senderLabel(message) {
  if (message.nguoi_gui_loai === "customer") {
    return "Bạn";
  }

  return message?.nhan_vien?.ho_ten || "Nhân viên hỗ trợ";
}

function formatCurrency(value) {
  const amount = Number(value || 0);

  return `${amount.toLocaleString("vi-VN")}đ`;
}

function formatDateTime(value) {
  if (!value) {
    return "-";
  }

  return new Intl.DateTimeFormat("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
    day: "2-digit",
    month: "2-digit",
  }).format(new Date(value));
}

async function openSuggestedProduct(product) {
  const maThuoc = String(product?.ma_thuoc || "").trim();
  const keyword = String(product?.ten_thuoc || "").trim();

  if (!maThuoc) {
    return;
  }

  panelOpen.value = false;

  await router.push({
    path: "/tim-kiem",
    query: {
      q: keyword || maThuoc,
      ma_thuoc: maThuoc,
    },
  });
}

async function proceedToCheckout(message) {
  const action = purchaseAction(message);
  const product = action?.san_pham;

  if (!product?.ma_thuoc) {
    showToast("Không tìm thấy sản phẩm để tạo giỏ hàng.", "error");
    return;
  }

  const requestedQuantity = Math.max(1, Number.parseInt(product.so_luong_de_xuat, 10) || 1);
  const availableStock = Math.max(0, Number(product.so_luong_ton || 0));

  if (availableStock <= 0) {
    showToast(`Sản phẩm ${product.ten_thuoc || ""} hiện đã hết hàng.`, "error");
    return;
  }

  customerStore.addToCart(product, requestedQuantity);

  if (requestedQuantity > availableStock) {
    showToast(`Đã thêm tối đa ${availableStock} ${product.don_vi_hien_thi || "đơn vị"} theo tồn kho hiện có.`);
  } else {
    showToast(`Đã thêm ${requestedQuantity} ${product.don_vi_hien_thi || "đơn vị"} vào giỏ hàng.`);
  }

  panelOpen.value = false;
  await router.push(action.checkout_link || "/thanh-toan");
}

function scrollToBottom() {
  if (!messagesRef.value) {
    return;
  }

  messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
}

function startPolling() {
  stopPolling();

  pollTimer = window.setInterval(() => {
    loadConversation({
      silent: true,
      includeMessages: panelOpen.value,
      markRead: panelOpen.value,
    });
  }, 10000);
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}
</script>

<style scoped>
.pc-support-chat {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 1090;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
}

.pc-support-chat__toggle {
  position: relative;
  min-width: 148px;
  min-height: 52px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 0 18px;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, #1652c5, #0d2b73);
  color: #fff;
  box-shadow: 0 18px 38px rgba(13, 43, 115, 0.28);
  font-weight: 800;
}

.pc-support-chat__badge {
  min-width: 21px;
  height: 21px;
  display: grid;
  place-items: center;
  padding: 0 4px;
  border-radius: 999px;
  background: #ff6b35;
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
}

.pc-support-chat__panel {
  width: min(520px, calc(100vw - 24px));
  display: grid;
  gap: 10px;
  padding: 14px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 24px 54px rgba(15, 31, 79, 0.22);
  backdrop-filter: blur(14px);
}

.pc-support-chat__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.pc-support-chat__head strong {
  display: block;
  color: #17345f;
  font-size: 1rem;
  font-weight: 800;
}

.pc-support-chat__head p,
.pc-support-chat__assigned {
  margin: 4px 0 0;
  color: #617892;
  font-size: 0.88rem;
}

.pc-support-chat__close {
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 999px;
  background: #f2f6ff;
  color: #29456f;
}

.pc-support-chat__messages {
  min-height: 220px;
  max-height: 300px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  overflow-y: auto;
}

.pc-support-chat__loading,
.pc-support-chat__empty {
  min-height: 220px;
  display: grid;
  place-items: center;
  gap: 10px;
  padding: 24px;
  border: 1px dashed rgba(22, 82, 197, 0.18);
  border-radius: 18px;
  background: #f8fbff;
  color: #617892;
  text-align: center;
}

.pc-support-chat__empty i {
  font-size: 2rem;
  color: #1652c5;
}

.pc-support-chat__empty strong {
  color: #17345f;
}

.pc-support-chat__message {
  display: flex;
}

.pc-support-chat__message.is-customer {
  justify-content: flex-end;
}

.pc-support-chat__message.is-staff {
  justify-content: flex-start;
}

.pc-support-chat__message.is-ai {
  justify-content: flex-start;
}

.pc-support-chat__bubble {
  max-width: 88%;
  display: grid;
  gap: 6px;
  padding: 10px 12px;
  border-radius: 16px;
  background: #f4f7ff;
  color: #243b5d;
  box-shadow: 0 10px 24px rgba(15, 31, 79, 0.06);
}

.pc-support-chat__message.is-customer .pc-support-chat__bubble {
  background: linear-gradient(135deg, #1652c5, #0d2b73);
  color: #fff;
  border-bottom-right-radius: 6px;
}

.pc-support-chat__message.is-staff .pc-support-chat__bubble {
  border-bottom-left-radius: 6px;
}

.pc-support-chat__message.is-ai .pc-support-chat__bubble {
  background: linear-gradient(135deg, #fff7e8, #ffe6bf);
  color: #7b4a05;
  border: 1px solid rgba(255, 173, 31, 0.24);
  border-bottom-left-radius: 6px;
}

.pc-support-chat__bubble strong {
  font-size: 0.82rem;
  font-weight: 800;
}

.pc-support-chat__intent {
  justify-self: start;
  padding: 3px 9px;
  border-radius: 999px;
  background: rgba(22, 82, 197, 0.12);
  color: #315794;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.pc-support-chat__purchase-action {
  margin-top: 2px;
}

.pc-support-chat__purchase-button {
  min-height: 38px;
  padding: 0 14px;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, #1652c5, #0d2b73);
  color: #fff;
  font-size: 0.82rem;
  font-weight: 800;
}

.pc-support-chat__bubble p {
  margin: 0;
  white-space: pre-wrap;
  line-height: 1.45;
}

.pc-support-chat__bubble small {
  opacity: 0.74;
  font-size: 0.74rem;
}

.pc-support-chat__suggestions {
  display: grid;
  gap: 8px;
}

.pc-support-chat__suggestion {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  gap: 8px;
  padding: 8px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.78);
}

.pc-support-chat__suggestion-thumb {
  position: relative;
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: linear-gradient(135deg, #eef5ff, #f8fbff);
  overflow: hidden;
  color: #1652c5;
}

.pc-support-chat__suggestion-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.pc-support-chat__suggestion-thumb i {
  font-size: 1.2rem;
}

.pc-support-chat__suggestion-promo {
  position: absolute;
  top: -6px;
  left: -6px;
  z-index: 2;
  max-width: 64px;
  padding: 3px 7px;
  border-radius: 999px;
  background: #eb3030;
  color: #fff;
  font-size: 0.6rem;
  font-weight: 900;
  line-height: 1;
  text-align: center;
  box-shadow: 0 8px 18px rgba(235, 48, 48, 0.24);
}

.pc-support-chat__suggestion-body {
  min-width: 0;
  display: grid;
  gap: 4px;
}

.pc-support-chat__suggestion-top {
  display: flex;
  align-items: start;
  justify-content: space-between;
  gap: 8px;
}

.pc-support-chat__suggestion-top strong {
  min-width: 0;
  font-size: 0.8rem;
  line-height: 1.35;
}

.pc-support-chat__suggestion-body p {
  margin: 0;
  color: inherit;
  opacity: 0.86;
  font-size: 0.76rem;
  line-height: 1.35;
}

.pc-support-chat__suggestion-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 10px;
  font-size: 0.74rem;
  font-weight: 700;
}

.pc-support-chat__suggestion-price {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.pc-support-chat__suggestion-price del {
  color: #8b9ab5;
  font-size: 0.68rem;
  font-weight: 700;
}

.pc-support-chat__suggestion-flag {
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(255, 132, 0, 0.16);
  color: #b05a00;
  font-size: 0.68rem;
  font-weight: 800;
  white-space: nowrap;
}

.pc-support-chat__suggestion-actions {
  display: flex;
  justify-content: flex-end;
}

.pc-support-chat__suggestion-actions button {
  padding: 6px 10px;
  border: 1px solid rgba(22, 82, 197, 0.16);
  border-radius: 999px;
  background: #fff;
  color: #1652c5;
  font-size: 0.72rem;
  font-weight: 800;
}

.pc-support-chat__quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.pc-support-chat__quick-action {
  padding: 6px 10px;
  border: 1px solid rgba(22, 82, 197, 0.14);
  border-radius: 999px;
  background: #f8fbff;
  color: #1652c5;
  font-size: 0.76rem;
  font-weight: 800;
}

.pc-support-chat__composer {
  display: grid;
  gap: 8px;
}

.pc-support-chat__composer textarea {
  width: 100%;
  min-height: 64px;
  padding: 10px 12px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 16px;
  background: #fff;
  outline: 0;
  resize: none;
}

.pc-support-chat__composer button {
  justify-self: end;
  min-width: 108px;
  border-radius: 14px;
}

.pc-support-chat-float-enter-active,
.pc-support-chat-float-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}

.pc-support-chat-float-enter-from,
.pc-support-chat-float-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

@media (max-width: 767.98px) {
  .pc-support-chat {
    right: 12px;
    bottom: 12px;
  }

  .pc-support-chat__panel {
    width: min(100vw - 24px, 100%);
    padding: 16px;
  }
}
</style>
