const fs = require('fs');

const file = 'src/components/Admin/TonKho/index.vue';
let content = fs.readFileSync(file, 'utf8');

// Thêm deleteLoThuoc vào import
if (!content.includes('deleteLoThuoc,')) {
    content = content.replace(
        'disposeLoThuoc,', 
        'disposeLoThuoc,\n  deleteLoThuoc,'
    );
}

// Thêm button Xóa lô ở alert (dòng 173)
const btn1 = `<button v-if="isAdminUser" class="btn btn-sm btn-outline-danger" :disabled="loading.disposeLotId === lo.id_lo" @click="deleteExpiredLot(lo)">
                      <span v-if="loading.disposeLotId === lo.id_lo" class="spinner-border spinner-border-sm me-1"></span>
                      Xóa lô
                    </button>`;
if (!content.includes('deleteExpiredLot(lo)')) {
    content = content.replace(
        `<button class="btn btn-sm btn-outline-primary" @click="openRelatedLotDetail(lo.id_thuoc)">Xem lô</button>`,
        `<button class="btn btn-sm btn-outline-primary" @click="openRelatedLotDetail(lo.id_thuoc)">Xem lô</button>\n                    ${btn1}`
    );
}

// Thêm button Xóa lô ở chi tiết lô (dòng 294)
const btn2 = `<button v-if="isAdminUser" class="btn btn-sm btn-outline-danger" :disabled="loading.disposeLotId === lo.id_lo" @click="deleteExpiredLot(lo)">
                    <span v-if="loading.disposeLotId === lo.id_lo" class="spinner-border spinner-border-sm me-1"></span>
                    Xóa lô
                  </button>`;
if (content.split('deleteExpiredLot').length < 3) {
    content = content.replace(
        `<button class="btn btn-sm btn-outline-primary" @click="openLotForm(lo)">Sửa lô</button>`,
        `${btn2}\n                  <button class="btn btn-sm btn-outline-primary" @click="openLotForm(lo)">Sửa lô</button>`
    );
}

// Thêm method deleteExpiredLot
const method = `
    async deleteExpiredLot(lo) {
      if (!this.isAdminUser) return;
      const confirmed = window.confirm("Bạn có chắc chắn muốn xóa hoàn toàn lô " + lo.so_lo + " khỏi hệ thống không? Khi xóa lô, bạn có thể xóa được thuốc nếu đây là lô duy nhất.");
      if (!confirmed) return;

      this.loading.disposeLotId = lo.id_lo;
      try {
        await deleteLoThuoc(lo.id_lo);
        alert("Đã xóa lô thành công.");
        await this.loadInventory();
        if (this.selectedThuoc) {
            this.selectedThuocLots = this.getLotsForThuoc(this.selectedThuoc);
        }
      } catch (err) {
        console.error(err);
        alert("Không thể xóa lô. Có thể lô này đã được bán hoặc có dữ liệu liên quan.");
      } finally {
        this.loading.disposeLotId = null;
      }
    },`;

if (!content.includes('async deleteExpiredLot(lo)')) {
    content = content.replace(
        `ensureModal() {`,
        `${method}\n    ensureModal() {`
    );
}

fs.writeFileSync(file, content, 'utf8');
