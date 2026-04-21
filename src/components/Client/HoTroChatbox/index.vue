<template>
  <div v-if="isCustomer" class="pc-support-chat">
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
              }"
            >
              <div class="pc-support-chat__bubble">
                <strong>{{ senderLabel(message) }}</strong>
                <p>{{ message.noi_dung }}</p>
                <small>{{ formatDateTime(message.thoi_gian) }}</small>
              </div>
            </article>
          </template>

          <div v-else class="pc-support-chat__empty">
            <i class="bi bi-headset"></i>
            <strong>Chưa có tin nhắn nào</strong>
            <p>Nhập câu hỏi của bạn. Nhân viên sẽ phản hồi ngay trong khung chat này.</p>
          </div>
        </div>

        <div v-if="conversation?.nhan_vien_phu_trach" class="pc-support-chat__assigned">
          Đang hỗ trợ: {{ conversation.nhan_vien_phu_trach.ho_ten }}
        </div>

        <form class="pc-support-chat__composer" @submit.prevent="sendMessage">
          <textarea
            v-model.trim="draftMessage"
            rows="2"
            placeholder="Nhập nội dung cần hỗ trợ..."
            :disabled="sending"
            @keydown.enter.exact.prevent="sendMessage"
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
import { getCustomerSupportConversation, sendCustomerSupportMessage } from "../../../api/supportApi";
import { getAuthType, isAuthenticatedState } from "../../../lib/authStorage";
import { SUPPORT_CHAT_OPEN_EVENT } from "../../../lib/supportChatEvents";
import { showToast } from "../../../lib/toast";

const panelOpen = ref(false);
const loading = ref(false);
const sending = ref(false);
const conversation = ref(null);
const draftMessage = ref("");
const messagesRef = ref(null);

let pollTimer = null;

const isCustomer = computed(() => isAuthenticatedState.value && getAuthType() === "customer");
const unreadCount = computed(() => Number(conversation.value?.so_tin_chua_doc || 0));
const statusLabel = computed(() => {
  const status = conversation.value?.trang_thai;

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

  if (isCustomer.value) {
    loadConversation({ silent: true, includeMessages: false });
    startPolling();
  }
});

onBeforeUnmount(() => {
  window.removeEventListener(SUPPORT_CHAT_OPEN_EVENT, handleOpenSupportChat);
  stopPolling();
});

watch(isCustomer, async (value) => {
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
  if (!isCustomer.value) {
    showToast("Vui lòng đăng nhập tài khoản khách hàng để chat hỗ trợ.", "error");
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
  if (!isCustomer.value) {
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
      showToast(error?.message || "Không thể tải hội thoại hỗ trợ.", "error");
    }
  } finally {
    if (!silent) {
      loading.value = false;
    }
  }
}

async function sendMessage() {
  if (!draftMessage.value || sending.value) {
    return;
  }

  sending.value = true;

  try {
    const response = await sendCustomerSupportMessage(draftMessage.value);
    conversation.value = response?.data || null;
    draftMessage.value = "";
    showToast("Đã gửi tin nhắn hỗ trợ.");
    await nextTick();
    scrollToBottom();
  } catch (error) {
    showToast(error?.message || "Không thể gửi tin nhắn hỗ trợ.", "error");
  } finally {
    sending.value = false;
  }
}

function senderLabel(message) {
  if (message.nguoi_gui_loai === "customer") {
    return "Bạn";
  }

  return message?.nhan_vien?.ho_ten || "Nhân viên hỗ trợ";
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
  width: min(380px, calc(100vw - 24px));
  display: grid;
  gap: 14px;
  padding: 18px;
  border: 1px solid rgba(22, 82, 197, 0.12);
  border-radius: 24px;
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
  min-height: 300px;
  max-height: 380px;
  display: grid;
  gap: 12px;
  overflow-y: auto;
}

.pc-support-chat__loading,
.pc-support-chat__empty {
  min-height: 300px;
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

.pc-support-chat__bubble {
  max-width: 84%;
  display: grid;
  gap: 6px;
  padding: 12px 14px;
  border-radius: 18px;
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

.pc-support-chat__bubble strong {
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

.pc-support-chat__composer {
  display: grid;
  gap: 10px;
}

.pc-support-chat__composer textarea {
  width: 100%;
  min-height: 82px;
  padding: 12px 14px;
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
