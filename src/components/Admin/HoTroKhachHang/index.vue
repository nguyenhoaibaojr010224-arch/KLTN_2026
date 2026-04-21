<template>
  <section class="content-card mb-4">
    <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
      <div>
        <div class="soft-badge soft-badge--teal mb-3">
          <i class="bi bi-chat-dots"></i>
          Hỗ trợ khách hàng
        </div>
        <h2 class="page-section-title">Trao đổi trực tiếp với khách hàng</h2>
        <p class="page-section-copy mb-0">
          Nhân viên và quản trị viên theo dõi câu hỏi từ khách hàng, trả lời ngay trong cùng một luồng hội thoại.
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="button" :disabled="loadingList" @click="refreshData">
          <span v-if="loadingList" class="spinner-border spinner-border-sm me-2"></span>
          Đồng bộ hội thoại
        </button>
      </div>
    </div>
  </section>

  <section class="row g-4">
    <div class="col-xl-4">
      <article class="content-card h-100">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
          <div>
            <h3 class="panel-title">Danh sách hội thoại</h3>
            <p class="panel-subtitle mb-0">{{ filteredConversations.length }} hội thoại</p>
          </div>
          <span class="soft-badge soft-badge--blue">{{ unreadConversationCount }} mới</span>
        </div>

        <div class="mb-3">
          <input
            v-model.trim="keyword"
            type="text"
            class="form-control"
            placeholder="Tìm theo tên khách, số điện thoại, email..."
          />
        </div>

        <div v-if="loadingList && !conversations.length" class="master-empty">
          <p class="mb-2 fw-semibold">Đang tải hội thoại hỗ trợ.</p>
          <p class="mb-0 text-secondary">Hệ thống đang đồng bộ dữ liệu chat từ khách hàng.</p>
        </div>

        <div v-else-if="filteredConversations.length" class="support-thread-list">
          <button
            v-for="item in filteredConversations"
            :key="item.id_hoi_thoai"
            type="button"
            class="support-thread-list__item"
            :class="{ active: item.id_hoi_thoai === activeConversationId }"
            @click="openConversation(item.id_hoi_thoai)"
          >
            <div class="support-thread-list__main">
              <div class="support-thread-list__top">
                <strong>{{ item.khach_hang?.ten_khach_hang || "Khách hàng" }}</strong>
                <span class="support-thread-list__contact">
                  {{ item.khach_hang?.so_dien_thoai || item.khach_hang?.email || "-" }}
                </span>
              </div>

              <p class="support-thread-list__preview">
                {{ item.tin_nhan_cuoi?.noi_dung || "Khách hàng chưa gửi tin nhắn nào." }}
              </p>
            </div>

            <div class="support-thread-list__meta">
              <span class="soft-badge" :class="statusBadgeClass(item.trang_thai)">
                {{ statusLabel(item.trang_thai) }}
              </span>
              <span v-if="item.so_tin_chua_doc" class="support-thread-list__count">
                {{ item.so_tin_chua_doc }} mới
              </span>
            </div>
          </button>
        </div>

        <div v-else class="master-empty">
          <p class="mb-2 fw-semibold">Chưa có hội thoại nào.</p>
          <p class="mb-0 text-secondary">Khi khách hàng nhắn hỗ trợ, cuộc trò chuyện sẽ xuất hiện ở đây.</p>
        </div>
      </article>
    </div>

    <div class="col-xl-8">
      <article class="content-card h-100">
        <template v-if="activeConversation">
          <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
              <h3 class="panel-title mb-2">
                {{ activeConversation.khach_hang?.ten_khach_hang || "Khách hàng" }}
              </h3>
              <p class="panel-subtitle mb-1">
                {{ activeConversation.khach_hang?.so_dien_thoai || "-" }} ·
                {{ activeConversation.khach_hang?.email || "Không có email" }}
              </p>
              <p class="panel-subtitle mb-0">
                Phụ trách:
                {{ activeConversation.nhan_vien_phu_trach?.ho_ten || "Chưa gán nhân viên" }}
              </p>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-start">
              <span class="soft-badge" :class="statusBadgeClass(activeConversation.trang_thai)">
                {{ statusLabel(activeConversation.trang_thai) }}
              </span>
              <button class="btn btn-outline-secondary" type="button" :disabled="loadingThread" @click="reloadActiveConversation">
                Tải lại
              </button>
              <button class="btn btn-outline-danger" type="button" :disabled="closingConversation" @click="handleCloseConversation">
                <span v-if="closingConversation" class="spinner-border spinner-border-sm me-2"></span>
                Đóng hội thoại
              </button>
            </div>
          </div>

          <div ref="messagesRef" class="support-chat__messages">
            <div v-if="loadingThread" class="support-chat__loading">
              <div class="spinner-border text-primary"></div>
              <span>Đang tải nội dung hội thoại...</span>
            </div>

            <template v-else-if="activeConversation.messages?.length">
              <article
                v-for="message in activeConversation.messages"
                :key="message.id_tin_nhan"
                class="support-chat__message"
                :class="{
                  'is-staff': message.nguoi_gui_loai === 'staff',
                  'is-customer': message.nguoi_gui_loai === 'customer',
                }"
              >
                <div class="support-chat__bubble">
                  <strong>{{ senderLabel(message) }}</strong>
                  <p>{{ message.noi_dung }}</p>
                  <small>{{ formatDateTime(message.thoi_gian) }}</small>
                </div>
              </article>
            </template>

            <div v-else class="master-empty">
              <p class="mb-2 fw-semibold">Chưa có tin nhắn nào trong hội thoại này.</p>
              <p class="mb-0 text-secondary">Khi khách hàng gửi nội dung, tin nhắn sẽ hiển thị tại đây.</p>
            </div>
          </div>

          <form class="support-chat__composer" @submit.prevent="sendReply">
            <textarea
              v-model.trim="replyMessage"
              class="form-control"
              rows="3"
              placeholder="Nhập nội dung phản hồi cho khách hàng..."
              :disabled="sendingReply"
              @keydown.enter.exact.prevent="sendReply"
            ></textarea>

            <div class="d-flex justify-content-end">
              <button class="btn btn-primary" type="submit" :disabled="sendingReply || !replyMessage">
                <span v-if="sendingReply" class="spinner-border spinner-border-sm me-2"></span>
                Gửi phản hồi
              </button>
            </div>
          </form>
        </template>

        <div v-else class="master-empty support-chat__empty">
          <p class="mb-2 fw-semibold">Chưa chọn hội thoại.</p>
          <p class="mb-0 text-secondary">Chọn một khách hàng bên trái để xem và trả lời tin nhắn hỗ trợ.</p>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import {
  closeSupportConversation,
  getSupportConversation,
  getSupportConversations,
  sendSupportMessage,
} from "../../../api/supportApi";
import { showToast } from "../../../lib/toast";

const keyword = ref("");
const conversations = ref([]);
const activeConversationId = ref(null);
const activeConversation = ref(null);
const loadingList = ref(false);
const loadingThread = ref(false);
const sendingReply = ref(false);
const closingConversation = ref(false);
const replyMessage = ref("");
const messagesRef = ref(null);

let pollTimer = null;

const filteredConversations = computed(() => {
  if (!keyword.value) {
    return conversations.value;
  }

  const normalized = keyword.value.toLowerCase();

  return conversations.value.filter((item) => {
    const name = item.khach_hang?.ten_khach_hang || "";
    const phone = item.khach_hang?.so_dien_thoai || "";
    const email = item.khach_hang?.email || "";
    const preview = item.tin_nhan_cuoi?.noi_dung || "";

    return [name, phone, email, preview].some((value) => value.toLowerCase().includes(normalized));
  });
});

const unreadConversationCount = computed(
  () => conversations.value.filter((item) => Number(item.so_tin_chua_doc || 0) > 0).length
);

onMounted(async () => {
  await loadConversations();
  startPolling();
});

onBeforeUnmount(() => {
  stopPolling();
});

watch(
  () => activeConversation.value?.messages?.length,
  async () => {
    if (!activeConversation.value) {
      return;
    }

    await nextTick();
    scrollMessagesToBottom();
  }
);

async function loadConversations({ silent = false } = {}) {
  if (!silent) {
    loadingList.value = true;
  }

  try {
    const response = await getSupportConversations();
    const nextConversations = Array.isArray(response?.data) ? response.data : [];
    conversations.value = nextConversations;

    if (!nextConversations.length) {
      activeConversationId.value = null;
      activeConversation.value = null;
      return;
    }

    const stillExists = nextConversations.some((item) => item.id_hoi_thoai === activeConversationId.value);

    if (!activeConversationId.value || !stillExists) {
      await openConversation(nextConversations[0].id_hoi_thoai, { preserveList: true });
    }
  } catch (error) {
    if (!silent) {
      showToast(error?.message || "Không thể tải danh sách hội thoại.", "error");
    }
  } finally {
    if (!silent) {
      loadingList.value = false;
    }
  }
}

async function openConversation(id, { preserveList = false, silent = false } = {}) {
  if (!id) {
    return;
  }

  activeConversationId.value = id;

  if (!silent) {
    loadingThread.value = true;
  }

  try {
    const response = await getSupportConversation(id);
    activeConversation.value = response?.data || null;

    if (!preserveList) {
      await loadConversations({ silent: true });
    } else {
      conversations.value = conversations.value.map((item) =>
        item.id_hoi_thoai === id
          ? {
              ...item,
              so_tin_chua_doc: 0,
            }
          : item
      );
    }
  } catch (error) {
    showToast(error?.message || "Không thể tải hội thoại chi tiết.", "error");
  } finally {
    if (!silent) {
      loadingThread.value = false;
    }
  }
}

async function sendReply() {
  if (!activeConversationId.value || !replyMessage.value || sendingReply.value) {
    return;
  }

  sendingReply.value = true;

  try {
    const response = await sendSupportMessage(activeConversationId.value, replyMessage.value);
    activeConversation.value = response?.data || null;
    replyMessage.value = "";
    await loadConversations({ silent: true });
    showToast("Đã gửi phản hồi cho khách hàng.");
  } catch (error) {
    showToast(error?.message || "Không thể gửi phản hồi.", "error");
  } finally {
    sendingReply.value = false;
  }
}

async function handleCloseConversation() {
  if (!activeConversationId.value || closingConversation.value) {
    return;
  }

  closingConversation.value = true;

  try {
    const response = await closeSupportConversation(activeConversationId.value);
    activeConversation.value = response?.data || null;
    await loadConversations({ silent: true });
    showToast("Đã đóng hội thoại hỗ trợ.");
  } catch (error) {
    showToast(error?.message || "Không thể đóng hội thoại.", "error");
  } finally {
    closingConversation.value = false;
  }
}

async function refreshData() {
  await loadConversations();

  if (activeConversationId.value) {
    await openConversation(activeConversationId.value, { silent: true, preserveList: true });
  }
}

async function reloadActiveConversation() {
  if (!activeConversationId.value) {
    return;
  }

  await openConversation(activeConversationId.value);
}

function startPolling() {
  stopPolling();

  pollTimer = window.setInterval(async () => {
    await loadConversations({ silent: true });

    if (activeConversationId.value) {
      await openConversation(activeConversationId.value, {
        silent: true,
        preserveList: true,
      });
    }
  }, 10000);
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
}

function statusLabel(status) {
  if (status === "dang_trao_doi") {
    return "Đang trao đổi";
  }

  if (status === "da_dong") {
    return "Đã đóng";
  }

  return "Mới";
}

function statusBadgeClass(status) {
  if (status === "dang_trao_doi") {
    return "soft-badge--blue";
  }

  if (status === "da_dong") {
    return "soft-badge--orange";
  }

  return "soft-badge--teal";
}

function senderLabel(message) {
  if (message.nguoi_gui_loai === "staff") {
    return message?.nhan_vien?.ho_ten || "Nhân viên";
  }

  return message?.khach_hang?.ten_khach_hang || "Khách hàng";
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

function scrollMessagesToBottom() {
  if (!messagesRef.value) {
    return;
  }

  messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
}
</script>

<style scoped>
.support-thread-list {
  display: grid;
  gap: 10px;
}

.support-thread-list__item {
  width: 100%;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid rgba(22, 82, 197, 0.08);
  border-radius: 18px;
  background: #f8fbff;
  text-align: left;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.support-thread-list__item:hover,
.support-thread-list__item.active {
  border-color: rgba(22, 82, 197, 0.22);
  box-shadow: 0 14px 28px rgba(15, 31, 79, 0.08);
  transform: translateY(-1px);
}

.support-thread-list__main {
  min-width: 0;
  display: grid;
  gap: 4px;
}

.support-thread-list__top {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.support-thread-list__top strong {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #19335e;
  font-size: 0.95rem;
  font-weight: 800;
}

.support-thread-list__contact {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #60738d;
  font-size: 0.84rem;
}

.support-thread-list__meta {
  display: grid;
  justify-items: end;
  gap: 8px;
}

.support-thread-list__count {
  color: #1652c5;
  font-size: 0.82rem;
  font-weight: 800;
}

.support-thread-list__preview {
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #4f6683;
  font-size: 0.88rem;
  line-height: 1.35;
}

.support-chat__messages {
  min-height: 420px;
  max-height: 520px;
  display: grid;
  gap: 12px;
  margin-bottom: 18px;
  overflow-y: auto;
}

.support-chat__loading {
  min-height: 240px;
  display: grid;
  place-items: center;
  gap: 12px;
  color: #60738d;
}

.support-chat__message {
  display: flex;
}

.support-chat__message.is-staff {
  justify-content: flex-end;
}

.support-chat__message.is-customer {
  justify-content: flex-start;
}

.support-chat__bubble {
  max-width: 78%;
  display: grid;
  gap: 6px;
  padding: 14px 16px;
  border-radius: 18px;
  background: #f5f8ff;
  color: #243b5d;
  box-shadow: 0 10px 24px rgba(15, 31, 79, 0.06);
}

.support-chat__message.is-staff .support-chat__bubble {
  background: linear-gradient(135deg, #1652c5, #0d2b73);
  color: #fff;
}

.support-chat__bubble strong {
  font-size: 0.84rem;
  font-weight: 800;
}

.support-chat__bubble p {
  margin: 0;
  white-space: pre-wrap;
  line-height: 1.5;
}

.support-chat__bubble small {
  opacity: 0.76;
  font-size: 0.74rem;
}

.support-chat__composer {
  display: grid;
  gap: 12px;
}

.support-chat__composer textarea {
  min-height: 100px;
  resize: none;
}

.support-chat__empty {
  min-height: 420px;
}

@media (max-width: 1199.98px) {
  .support-chat__messages {
    min-height: 320px;
  }
}

@media (max-width: 767.98px) {
  .support-thread-list__item {
    grid-template-columns: 1fr;
    align-items: start;
  }

  .support-thread-list__top {
    flex-direction: column;
    align-items: start;
  }

  .support-thread-list__meta {
    justify-items: start;
    grid-auto-flow: column;
    justify-content: start;
  }

  .support-thread-list__preview {
    white-space: normal;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }
}
</style>
