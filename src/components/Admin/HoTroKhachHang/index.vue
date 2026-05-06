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
                <strong>{{ customerName(item) }}</strong>
                <span class="support-thread-list__contact">
                  {{ customerContact(item) }}
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
                {{ customerName(activeConversation) }}
              </h3>
              <p class="panel-subtitle mb-1">
                {{ customerContact(activeConversation) }}
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
              <button class="btn btn-danger" type="button" :disabled="deletingConversation" @click="handleDeleteConversation">
                <span v-if="deletingConversation" class="spinner-border spinner-border-sm me-2"></span>
                Xóa hội thoại
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
                  'is-ai': message.nguoi_gui_loai === 'ai',
                }"
              >
                <div class="support-chat__bubble">
                  <strong>{{ resolveSenderLabel(message) }}</strong>
                  <div v-if="resolveIntentLabel(message)" class="support-chat__intent">
                    {{ resolveIntentLabel(message) }}
                  </div>
                  <p>{{ message.noi_dung }}</p>
                  <div v-if="suggestedProducts(message).length" class="support-chat__suggestions">
                    <article
                      v-for="product in suggestedProducts(message)"
                      :key="`${message.id_tin_nhan}-${product.ma_thuoc || product.ten_thuoc}`"
                      class="support-chat__suggestion"
                    >
                      <div class="support-chat__suggestion-thumb">
                        <span v-if="hasRealPromotion(product)" class="support-chat__suggestion-promo">
                          {{ promotionLabel(product) }}
                        </span>
                        <img
                          v-if="product.hinh_anh_url"
                          :src="product.hinh_anh_url"
                          :alt="product.ten_thuoc"
                        />
                        <i v-else class="bi bi-capsule"></i>
                      </div>

                      <div class="support-chat__suggestion-body">
                        <div class="support-chat__suggestion-top">
                          <strong>{{ product.ten_thuoc }}</strong>
                          <span v-if="product.la_thuoc_ke_don" class="support-chat__suggestion-flag">
                            Kê đơn
                          </span>
                        </div>
                        <p v-if="product.nhan">{{ product.nhan }}</p>
                        <div class="support-chat__suggestion-meta">
                          <span class="support-chat__suggestion-price">
                            <span>{{ formatCurrency(product.gia_ban) }}/{{ product.don_vi_hien_thi || "đơn vị" }}</span>
                            <del v-if="hasRealPromotion(product)">{{ formatCurrency(product.gia_niem_yet) }}</del>
                          </span>
                          <span>Còn {{ product.so_luong_ton || 0 }} {{ product.don_vi_ton || product.don_vi_hien_thi || "đơn vị" }}</span>
                        </div>
                      </div>
                    </article>
                  </div>
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
import { useRoute, useRouter } from "vue-router";
import {
  closeSupportConversation,
  deleteSupportConversation,
  getSupportConversation,
  getSupportConversations,
  sendSupportMessage,
} from "../../../api/supportApi";
import { normalizeApiError } from "../../../lib/errorMessages";
import { showToast } from "../../../lib/toast";

const route = useRoute();
const router = useRouter();
const keyword = ref("");
const conversations = ref([]);
const activeConversationId = ref(null);
const activeConversation = ref(null);
const loadingList = ref(false);
const loadingThread = ref(false);
const sendingReply = ref(false);
const closingConversation = ref(false);
const deletingConversation = ref(false);
const replyMessage = ref("");
const messagesRef = ref(null);

let pollTimer = null;
let activeConversationRequestId = 0;

function normalizeError(error, fallback = "Không thể xử lý yêu cầu hỗ trợ.") {
  return normalizeApiError(error, fallback, {
    noi_dung: "nội dung phản hồi",
    message: "nội dung phản hồi",
  });
}

const filteredConversations = computed(() => {
  if (!keyword.value) {
    return conversations.value;
  }

  const normalized = keyword.value.toLowerCase();

  return conversations.value.filter((item) => {
    const name = customerName(item);
    const phone = item.khach_hang?.so_dien_thoai || "";
    const email = item.khach_hang?.email || "";
    const contact = customerContact(item);
    const preview = item.tin_nhan_cuoi?.noi_dung || "";

    return [name, phone, email, contact, preview].some((value) => value.toLowerCase().includes(normalized));
  });
});

const unreadConversationCount = computed(
  () => conversations.value.filter((item) => Number(item.so_tin_chua_doc || 0) > 0).length
);
const requestedConversationId = computed(() => {
  const raw = Number(route.query.hoi_thoai || 0);
  return Number.isFinite(raw) && raw > 0 ? raw : null;
});

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

watch(
  requestedConversationId,
  async (value) => {
    if (!value || value === activeConversationId.value) {
      return;
    }

    const exists = conversations.value.some((item) => item.id_hoi_thoai === value);
    if (exists) {
      await openConversation(value, { preserveList: true, syncRoute: false });
    }
  },
  { immediate: true }
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
      syncConversationQuery();
      return;
    }

    if (requestedConversationId.value) {
      const requestedExists = nextConversations.some(
        (item) => item.id_hoi_thoai === requestedConversationId.value
      );

      if (requestedExists && requestedConversationId.value !== activeConversationId.value) {
        await openConversation(requestedConversationId.value, {
          preserveList: true,
          syncRoute: false,
        });
        return;
      }
    }

    const stillExists = nextConversations.some((item) => item.id_hoi_thoai === activeConversationId.value);

    if (!activeConversationId.value || !stillExists) {
      await openConversation(nextConversations[0].id_hoi_thoai, { preserveList: true });
    }
  } catch (error) {
    if (!silent) {
      showToast(normalizeError(error, "Không thể tải danh sách hội thoại."), "error");
    }
  } finally {
    if (!silent) {
      loadingList.value = false;
    }
  }
}

async function openConversation(
  id,
  { preserveList = false, silent = false, syncRoute = true, force = false } = {}
) {
  if (!id) {
    return;
  }

  if (id === activeConversationId.value && activeConversation.value && preserveList && !force) {
    if (syncRoute) {
      syncConversationQuery(id);
    }
    return;
  }

  const requestId = ++activeConversationRequestId;
  activeConversationId.value = id;
  markConversationReadLocally(id);

  if (syncRoute) {
    syncConversationQuery(id);
  }

  if (!silent) {
    loadingThread.value = true;
  }

  try {
    const response = await getSupportConversation(id);
    if (requestId !== activeConversationRequestId) {
      return;
    }

    activeConversation.value = response?.data || null;
    replyMessage.value = "";
    syncConversationSummary(activeConversation.value, { markRead: true });
  } catch (error) {
    if (requestId === activeConversationRequestId) {
      showToast(normalizeError(error, "Không thể tải hội thoại chi tiết."), "error");
    }
  } finally {
    if (!silent && requestId === activeConversationRequestId) {
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
    syncConversationSummary(activeConversation.value, { markRead: true });
    showToast("Đã gửi phản hồi cho khách hàng.");
  } catch (error) {
    showToast(normalizeError(error, "Không thể gửi phản hồi."), "error");
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
    syncConversationSummary(activeConversation.value, { markRead: true });
    showToast("Đã đóng hội thoại hỗ trợ.");
  } catch (error) {
    showToast(normalizeError(error, "Không thể đóng hội thoại."), "error");
  } finally {
    closingConversation.value = false;
  }
}

async function handleDeleteConversation() {
  if (!activeConversationId.value || deletingConversation.value) {
    return;
  }

  const confirmed = window.confirm("Bạn có chắc muốn xóa hội thoại này không?");

  if (!confirmed) {
    return;
  }

  deletingConversation.value = true;
  const deletingId = activeConversationId.value;

  try {
    await deleteSupportConversation(deletingId);

    const remaining = conversations.value.filter((item) => item.id_hoi_thoai !== deletingId);
    conversations.value = remaining;
    activeConversationId.value = null;
    activeConversation.value = null;

    if (remaining.length) {
      await openConversation(remaining[0].id_hoi_thoai, { preserveList: true });
    } else {
      syncConversationQuery();
    }

    showToast("Đã xóa hội thoại hỗ trợ.");
  } catch (error) {
    showToast(normalizeError(error, "Không thể xóa hội thoại."), "error");
  } finally {
    deletingConversation.value = false;
  }
}

async function refreshData() {
  await loadConversations();

  if (activeConversationId.value) {
    await openConversation(activeConversationId.value, {
      silent: true,
      preserveList: true,
      force: true,
    });
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
    const previousActive = findConversationSummary(activeConversationId.value);
    await loadConversations({ silent: true });
    const nextActive = findConversationSummary(activeConversationId.value);

    if (activeConversationId.value && shouldRefreshActiveConversation(previousActive, nextActive)) {
      await openConversation(activeConversationId.value, {
        silent: true,
        preserveList: true,
        force: true,
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

function syncConversationQuery(id = null) {
  const currentId = route.query.hoi_thoai ? String(route.query.hoi_thoai) : "";
  const nextId = id ? String(id) : "";

  if (currentId === nextId) {
    return;
  }

  const nextQuery = { ...route.query };

  if (id) {
    nextQuery.hoi_thoai = id;
  } else {
    delete nextQuery.hoi_thoai;
  }

  router.replace({
    path: route.path,
    query: nextQuery,
  });
}

function findConversationSummary(id) {
  if (!id) {
    return null;
  }

  return conversations.value.find((item) => item.id_hoi_thoai === id) || null;
}

function shouldRefreshActiveConversation(previous, next) {
  if (!activeConversationId.value) {
    return false;
  }

  if (!previous || !next) {
    return true;
  }

  return (
    previous.thoi_gian_tin_nhan_cuoi !== next.thoi_gian_tin_nhan_cuoi ||
    Number(previous.so_tin_chua_doc || 0) !== Number(next.so_tin_chua_doc || 0) ||
    previous.trang_thai !== next.trang_thai
  );
}

function markConversationReadLocally(id) {
  if (!id) {
    return;
  }

  conversations.value = conversations.value.map((item) =>
    item.id_hoi_thoai === id
      ? {
          ...item,
          so_tin_chua_doc: 0,
        }
      : item
  );
}

function syncConversationSummary(conversation, { markRead = false } = {}) {
  if (!conversation?.id_hoi_thoai) {
    return;
  }

  const summary = {
    id_hoi_thoai: conversation.id_hoi_thoai,
    trang_thai: conversation.trang_thai,
    thoi_gian_tin_nhan_cuoi: conversation.thoi_gian_tin_nhan_cuoi,
    so_tin_chua_doc: markRead ? 0 : Number(conversation.so_tin_chua_doc || 0),
    khach_hang: conversation.khach_hang,
    nhan_vien_phu_trach: conversation.nhan_vien_phu_trach,
    tin_nhan_cuoi: conversation.messages?.length
      ? conversation.messages[conversation.messages.length - 1]
      : conversation.tin_nhan_cuoi,
  };

  const existingIndex = conversations.value.findIndex(
    (item) => item.id_hoi_thoai === conversation.id_hoi_thoai
  );

  if (existingIndex === -1) {
    conversations.value = [summary, ...conversations.value];
    return;
  }

  conversations.value = conversations.value.map((item, index) =>
    index === existingIndex
      ? {
          ...item,
          ...summary,
        }
      : item
  );
}

function customerName(conversation) {
  return conversation?.khach_hang?.ten_khach_hang || "Khách Vãng Lai";
}

function customerContact(conversation) {
  const customer = conversation?.khach_hang;

  if (customer?.la_khach_vang_lai) {
    return "Khách chưa đăng nhập";
  }

  return customer?.so_dien_thoai || customer?.email || "-";
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

  if (intent === "tra_cuu_thuoc") {
    return "Tra cứu thuốc";
  }

  return "";
}

function senderLabel(message) {
  if (message.nguoi_gui_loai === "staff") {
    return message?.nhan_vien?.ho_ten || "Nhân viên";
  }

  return message?.khach_hang?.ten_khach_hang || "Khách Vãng Lai";
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

.support-chat__message.is-ai {
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

.support-chat__message.is-ai .support-chat__bubble {
  background: linear-gradient(135deg, #fff7e8, #ffe6bf);
  color: #7b4a05;
  border: 1px solid rgba(255, 173, 31, 0.24);
}

.support-chat__bubble strong {
  font-size: 0.84rem;
  font-weight: 800;
}

.support-chat__intent {
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

.support-chat__bubble p {
  margin: 0;
  white-space: pre-wrap;
  line-height: 1.5;
}

.support-chat__bubble small {
  opacity: 0.76;
  font-size: 0.74rem;
}

.support-chat__suggestions {
  display: grid;
  gap: 8px;
}

.support-chat__suggestion {
  display: grid;
  grid-template-columns: 56px minmax(0, 1fr);
  gap: 10px;
  padding: 10px;
  border: 1px solid rgba(22, 82, 197, 0.1);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.78);
}

.support-chat__suggestion-thumb {
  position: relative;
  width: 56px;
  height: 56px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: linear-gradient(135deg, #eef5ff, #f8fbff);
  overflow: hidden;
  color: #1652c5;
}

.support-chat__suggestion-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.support-chat__suggestion-thumb i {
  font-size: 1.25rem;
}

.support-chat__suggestion-promo {
  position: absolute;
  top: -6px;
  left: -6px;
  z-index: 2;
  max-width: 68px;
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

.support-chat__suggestion-body {
  min-width: 0;
  display: grid;
  gap: 4px;
}

.support-chat__suggestion-top {
  display: flex;
  align-items: start;
  justify-content: space-between;
  gap: 8px;
}

.support-chat__suggestion-top strong {
  min-width: 0;
  font-size: 0.86rem;
  line-height: 1.35;
}

.support-chat__suggestion-body p {
  margin: 0;
  color: inherit;
  opacity: 0.86;
  font-size: 0.77rem;
  line-height: 1.35;
}

.support-chat__suggestion-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 10px;
  font-size: 0.75rem;
  font-weight: 700;
}

.support-chat__suggestion-price {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.support-chat__suggestion-price del {
  color: #8b9ab5;
  font-size: 0.68rem;
  font-weight: 700;
}

.support-chat__suggestion-flag {
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(255, 132, 0, 0.16);
  color: #b05a00;
  font-size: 0.68rem;
  font-weight: 800;
  white-space: nowrap;
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
