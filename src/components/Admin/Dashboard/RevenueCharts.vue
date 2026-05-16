<template>
  <div class="rv">
    <!-- KPI Cards -->
    <div class="rv-kpi">
      <div class="rv-kpi__card rv-kpi__card--blue">
        <div class="rv-kpi__icon"><i class="bi bi-cash-stack"></i></div>
        <div><span>Tổng doanh thu</span><strong>{{ fmtShort(ov.tong_doanh_thu) }}</strong></div>
      </div>
      <div class="rv-kpi__card rv-kpi__card--teal">
        <div class="rv-kpi__icon"><i class="bi bi-receipt-cutoff"></i></div>
        <div><span>Tổng đơn hàng</span><strong>{{ (ov.tong_don_hang||0).toLocaleString('vi-VN') }}</strong></div>
      </div>
      <div class="rv-kpi__card rv-kpi__card--amber">
        <div class="rv-kpi__icon"><i class="bi bi-lightning-charge"></i></div>
        <div><span>Hôm nay</span><strong>{{ fmtShort(ov.doanh_thu_hom_nay) }}</strong></div>
      </div>
      <div class="rv-kpi__card" :class="ov.tang_truong_phan_tram >= 0 ? 'rv-kpi__card--green' : 'rv-kpi__card--red'">
        <div class="rv-kpi__icon"><i class="bi bi-graph-up-arrow"></i></div>
        <div><span>Tăng trưởng</span><strong>{{ ov.tang_truong_phan_tram >= 0 ? '+' : '' }}{{ ov.tang_truong_phan_tram||0 }}%</strong></div>
      </div>
    </div>

    <!-- Row: Monthly bar + Donut -->
    <div class="rv-row">
      <div class="rv-card rv-card--2x">
        <div class="rv-card__head">
          <div><em>Xu hướng</em><h4>Doanh thu theo tháng</h4></div>
          <div class="rv-card__stat"><span>Tháng này</span><strong>{{ fmt(ov.doanh_thu_thang_nay) }}</strong></div>
        </div>
        <div class="rv-bars">
          <div v-for="m in monthly" :key="m.thang" class="rv-bars__col">
            <div class="rv-bars__val">{{ fmtShort(m.doanh_thu) }}</div>
            <div class="rv-bars__track"><span class="rv-bars__fill rv-bars__fill--gradient" :style="{height:pct(m.doanh_thu,maxM)+'%'}"></span></div>
            <strong>{{ fmtMonth(m.thang) }}</strong>
            <small>{{ Number(m.so_don).toLocaleString('vi-VN') }} đơn</small>
          </div>
        </div>
      </div>
      <div class="rv-card">
        <div class="rv-card__head"><div><em>Phân bổ</em><h4>Thanh toán</h4></div></div>
        <div class="rv-donut">
          <svg viewBox="0 0 180 180" class="rv-donut__svg">
            <circle v-for="(s,i) in donutSegs" :key="i" cx="90" cy="90" r="70" fill="none" :stroke="s.color" stroke-width="24" :stroke-dasharray="s.da" :stroke-dashoffset="s.off" stroke-linecap="round"/>
            <text x="90" y="86" text-anchor="middle" class="rv-donut__label">Tổng</text>
            <text x="90" y="108" text-anchor="middle" class="rv-donut__value">{{ fmt(pmTotal) }}</text>
          </svg>
          <div class="rv-donut__legend">
            <div v-for="(pm,i) in payments" :key="i" class="rv-donut__item">
              <span class="rv-donut__dot" :style="{background:COLORS[i]}"></span>
              <span>{{ pm.phuong_thuc }}</span>
              <strong>{{ fmt(pm.tong_tien) }}</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row: Daily trend + Top products -->
    <div class="rv-row">
      <div class="rv-card rv-card--2x">
        <div class="rv-card__head"><div><em>Chi tiết tháng này</em><h4>Doanh thu theo ngày</h4></div></div>
        <div class="rv-area" @mouseleave="hoveredDay=null">
          <div v-if="daily.length" class="rv-area__wrap">
            <svg :viewBox="`0 0 ${svgW} 160`" preserveAspectRatio="none" class="rv-area__svg">
              <defs>
                <linearGradient id="ag" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#6366f1" stop-opacity="0.28"/><stop offset="50%" stop-color="#3b82f6" stop-opacity="0.12"/><stop offset="100%" stop-color="#06b6d4" stop-opacity="0.02"/></linearGradient>
                <linearGradient id="lg" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#6366f1"/><stop offset="50%" stop-color="#3b82f6"/><stop offset="100%" stop-color="#06b6d4"/></linearGradient>
              </defs>
              <path :d="areaD" fill="url(#ag)"/>
              <polyline :points="lineD" fill="none" stroke="url(#lg)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
              <line v-if="hoveredDay!==null" :x1="dots[hoveredDay]?.x" :y1="0" :x2="dots[hoveredDay]?.x" y2="155" stroke="#6366f1" stroke-width="1" stroke-dasharray="4 3" opacity="0.4"/>
              <g v-for="(p,i) in dots" :key="i">
                <circle :cx="p.x" :cy="p.y" :r="hoveredDay===i?6:3" :fill="hoveredDay===i?'#fff':'#3b82f6'" :stroke="hoveredDay===i?'#6366f1':'none'" :stroke-width="hoveredDay===i?2.5:0" style="transition:r .15s,fill .15s" />
                <circle v-if="hoveredDay===i" :cx="p.x" :cy="p.y" r="12" fill="#6366f1" opacity="0.12" class="rv-area__pulse"/>
                <rect :x="p.x-14" :y="0" width="28" :height="155" fill="transparent" @mouseenter="hoveredDay=i" @mouseleave="hoveredDay=null" style="cursor:pointer"/>
              </g>
            </svg>
            <Transition name="rv-tip">
              <div v-if="hoveredDay!==null && dots[hoveredDay]" class="rv-area__tooltip" :style="{left: tooltipLeft+'px', top: tooltipTop+'px'}">
                <div class="rv-area__tooltip-date">{{ fmtDate(daily[hoveredDay]?.ngay) }}</div>
                <div class="rv-area__tooltip-rev">{{ fmt(daily[hoveredDay]?.doanh_thu) }}</div>
                <div class="rv-area__tooltip-orders">{{ daily[hoveredDay]?.so_don }} đơn hàng</div>
              </div>
            </Transition>
          </div>
          <div v-if="daily.length" class="rv-area__labels">
            <span v-for="(d,i) in daily" :key="i" :class="{'rv-area__labels--active': hoveredDay===i}" @mouseenter="hoveredDay=i">{{ i % Math.ceil(daily.length/8)===0 ? d.ngay.slice(8) : '' }}</span>
          </div>
          <div v-else class="rv-empty">Chưa có dữ liệu</div>
        </div>

        <!-- Daily mini-stats to fill space -->
        <div v-if="daily.length" class="rv-daily-stats">
          <div class="rv-daily-stats__card">
            <i class="bi bi-calendar-check"></i>
            <div><span>Ngày bán cao nhất</span><strong>{{ fmtDate(bestDay.ngay) }}</strong><small>{{ fmt(bestDay.doanh_thu) }}</small></div>
          </div>
          <div class="rv-daily-stats__card">
            <i class="bi bi-bar-chart-line"></i>
            <div><span>Trung bình/ngày</span><strong>{{ fmtShort(avgDaily) }}</strong><small>{{ daily.length }} ngày ghi nhận</small></div>
          </div>
          <div class="rv-daily-stats__card">
            <i class="bi bi-cart-check"></i>
            <div><span>Tổng đơn tháng này</span><strong>{{ totalDailyOrders.toLocaleString('vi-VN') }}</strong><small>{{ fmt(totalDailyRevenue) }}</small></div>
          </div>
        </div>
      </div>
      <div class="rv-card">
        <div class="rv-card__head"><div><em>Bán chạy nhất</em><h4>Top sản phẩm</h4></div></div>
        <div class="rv-products">
          <div v-for="(p,i) in visibleProducts" :key="i" class="rv-products__item">
            <span class="rv-products__rank" :class="['--gold','--silver','--bronze'][i]||''">{{ i+1 }}</span>
            <div class="rv-products__info">
              <strong :title="p.ten_thuoc">{{ p.ten_thuoc?.length>30 ? p.ten_thuoc.slice(0,30)+'…' : p.ten_thuoc }}</strong>
              <div class="rv-products__bar"><span :style="{width:pct(p.tong_doanh_thu,maxP)+'%'}"></span></div>
              <small>{{ fmtShort(p.tong_doanh_thu) }} · {{ Number(p.tong_so_luong).toLocaleString('vi-VN') }} SP</small>
            </div>
          </div>
        </div>
        <button v-if="products.length>4" class="rv-products__more" @click="showAllProducts=!showAllProducts">
          <i :class="showAllProducts?'bi bi-chevron-up':'bi bi-chevron-down'"></i>
          {{ showAllProducts ? 'Thu gọn' : `Xem thêm ${products.length-4} sản phẩm` }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="rv-empty"><span class="spinner-border spinner-border-sm me-2"></span>Đang tải tổng quan...</div>
    <div v-if="error" class="rv-empty rv-empty--err">{{ error }}</div>
  </div>
</template>

<script>
import { getRevenueOverview } from '../../../api/dashboardApi';
import { normalizeApiError } from '../../../lib/errorMessages';
const CF = new Intl.NumberFormat('vi-VN',{style:'currency',currency:'VND',maximumFractionDigits:0});
const COLORS = ['#3b82f6','#14b8a6','#f59e0b','#ef4444','#8b5cf6','#ec4899'];
export default {
  name:'RevenueCharts',
  data:()=>({data:null,loading:false,error:'',COLORS,hoveredDay:null,showAllProducts:false}),
  created(){this.load()},
  computed:{
    ov(){return this.data?.tong_quan||{}},
    monthly(){return this.data?.doanh_thu_theo_thang||[]},
    daily(){return this.data?.doanh_thu_theo_ngay||[]},
    payments(){return this.data?.phuong_thuc_thanh_toan||[]},
    products(){return this.data?.san_pham_ban_chay||[]},
    visibleProducts(){return this.showAllProducts?this.products:this.products.slice(0,4)},
    bestDay(){if(!this.daily.length)return{ngay:'',doanh_thu:0};return this.daily.reduce((a,b)=>+b.doanh_thu>+a.doanh_thu?b:a,this.daily[0])},
    avgDaily(){if(!this.daily.length)return 0;return this.daily.reduce((s,d)=>s+ +d.doanh_thu,0)/this.daily.length},
    totalDailyOrders(){return this.daily.reduce((s,d)=>s+ +d.so_don,0)},
    totalDailyRevenue(){return this.daily.reduce((s,d)=>s+ +d.doanh_thu,0)},
    maxM(){return Math.max(...this.monthly.map(m=>+m.doanh_thu),1)},
    maxP(){return Math.max(...this.products.map(p=>+p.tong_doanh_thu),1)},
    maxD(){return Math.max(...this.daily.map(d=>+d.doanh_thu),1)},
    pmTotal(){return this.payments.reduce((s,m)=>s+ +m.tong_tien,0)},
    donutSegs(){
      const t=this.pmTotal||1,c=2*Math.PI*70;let cum=0;
      return this.payments.map((m,i)=>{const p=+m.tong_tien/t,da=p*c;const s={color:COLORS[i%6],da:`${da} ${c-da}`,off:-cum};cum+=da;return s})
    },
    svgW(){return Math.max(this.daily.length*30,200)},
    dots(){
      if(!this.daily.length)return[];
      const w=this.svgW;
      return this.daily.map((d,i)=>({x:15+i*((w-30)/Math.max(this.daily.length-1,1)),y:145-(+d.doanh_thu/this.maxD)*130}))
    },
    lineD(){return this.dots.map(p=>`${p.x},${p.y}`).join(' ')},
    areaD(){if(!this.dots.length)return'';const p=this.dots;return`M${p[0].x},${p[0].y} `+p.slice(1).map(d=>`L${d.x},${d.y}`).join(' ')+` L${p[p.length-1].x},155 L${p[0].x},155 Z`},
    tooltipLeft(){if(this.hoveredDay===null||!this.dots[this.hoveredDay])return 0;const d=this.dots[this.hoveredDay];const pct=d.x/this.svgW;const el=this.$el?.querySelector('.rv-area__wrap');return el?(pct*el.clientWidth):0},
    tooltipTop(){if(this.hoveredDay===null||!this.dots[this.hoveredDay])return 0;const d=this.dots[this.hoveredDay];const el=this.$el?.querySelector('.rv-area__wrap');return el?(d.y/160*el.clientHeight-70):0},
  },
  methods:{
    async load(){this.loading=true;this.error='';try{const r=await getRevenueOverview(3);this.data=r.data}catch(e){this.error=normalizeApiError(e,'Lỗi tải dữ liệu.')}finally{this.loading=false}},
    fmt(v){const n=+v||0;if(Math.abs(n)>=1e9){const ty=n/1e9;return(Math.abs(ty)>=10?Math.round(ty):ty.toFixed(2).replace('.',','))+' tỷ đ'}return CF.format(n)},
    fmtShort(v){const n=+v||0;if(n>=1e9)return(n/1e9).toFixed(2).replace('.',',')+' tỷ';if(n>=1e6)return(n/1e6).toFixed(1)+' tr';if(n>=1e3)return Math.round(n/1e3)+'k';return n.toLocaleString('vi-VN')},
    pct(v,m){return Math.max(6,Math.round((+v/m)*100))},
    fmtMonth(ym){if(!ym)return'';const[y,m]=ym.split('-');return`T${+m}/${y.slice(2)}`},
    fmtDate(d){if(!d)return'';const[y,m,dd]=d.split('-');return`${dd}/${m}/${y}`},
  },
}
</script>

<style scoped>
.rv{display:flex;flex-direction:column;gap:1.25rem}

/* KPI */
.rv-kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:.875rem}
.rv-kpi__card{display:flex;align-items:center;gap:14px;padding:20px;border-radius:20px;background:#fff;border:1px solid rgba(22,82,197,.06);box-shadow:0 2px 12px rgba(15,31,79,.04);transition:transform .2s,box-shadow .2s}
.rv-kpi__card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(15,31,79,.1)}
.rv-kpi__icon{width:52px;height:52px;border-radius:16px;display:grid;place-items:center;color:#fff;font-size:1.35rem;flex-shrink:0}
.rv-kpi__card--blue .rv-kpi__icon{background:linear-gradient(135deg,#1652c5,#3b82f6)}
.rv-kpi__card--teal .rv-kpi__icon{background:linear-gradient(135deg,#0891b2,#14b8a6)}
.rv-kpi__card--amber .rv-kpi__icon{background:linear-gradient(135deg,#d97706,#f59e0b)}
.rv-kpi__card--green .rv-kpi__icon{background:linear-gradient(135deg,#059669,#10b981)}
.rv-kpi__card--red .rv-kpi__icon{background:linear-gradient(135deg,#dc2626,#ef4444)}
.rv-kpi__card span{display:block;color:#64748b;font-size:.8rem;font-weight:600}
.rv-kpi__card strong{display:block;margin-top:2px;color:#0f172a;font-size:1.35rem;font-weight:900}

/* Layout */
.rv-row{display:grid;grid-template-columns:2fr 1fr;gap:1.25rem}
.rv-card{border:1px solid rgba(22,82,197,.06);border-radius:22px;padding:24px;background:#fff;box-shadow:0 2px 12px rgba(15,31,79,.04)}
.rv-card--2x{grid-column:auto}
.rv-card__head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:20px}
.rv-card__head em{display:block;font-style:normal;color:#1652c5;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:3px}
.rv-card__head h4{margin:0;color:#0f172a;font-size:1.1rem;font-weight:800}
.rv-card__stat span{display:block;color:#64748b;font-size:.8rem;font-weight:600;text-align:right}
.rv-card__stat strong{display:block;color:#059669;font-size:1.1rem;font-weight:900}

/* Bar chart (unified style for monthly & weekly) */
.rv-bars{display:flex;gap:14px;align-items:flex-end;overflow-x:auto;padding:4px 0}
.rv-bars__col{flex:1;min-width:56px;text-align:center}
.rv-bars__val{color:#0f172a;font-size:.68rem;font-weight:800;margin-bottom:6px;line-height:1.2}
.rv-bars__track{height:200px;display:flex;align-items:flex-end;justify-content:center}
.rv-bars__fill{width:42px;min-height:4px;border-radius:8px 8px 3px 3px;transition:height .6s cubic-bezier(.4,0,.2,1)}
.rv-bars__fill--gradient{background:linear-gradient(180deg,#60a5fa 0%,#1652c5 100%);box-shadow:0 6px 20px rgba(59,130,246,.3)}
.rv-bars__col:hover .rv-bars__fill--gradient{background:linear-gradient(180deg,#93c5fd 0%,#2563eb 100%);box-shadow:0 8px 28px rgba(59,130,246,.4)}
.rv-bars__col strong{display:block;margin-top:8px;color:#0f172a;font-size:.78rem;font-weight:800}
.rv-bars__col small{color:#94a3b8;font-size:.7rem;font-weight:600}

/* Donut */
.rv-donut{display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.rv-donut__svg{width:150px;height:150px;flex-shrink:0;transform:rotate(-90deg)}
.rv-donut__label{font-size:11px;fill:#94a3b8;font-weight:600;transform:rotate(90deg);transform-origin:90px 86px}
.rv-donut__value{font-size:10px;fill:#0f172a;font-weight:900;transform:rotate(90deg);transform-origin:90px 108px}
.rv-donut__legend{flex:1;display:flex;flex-direction:column;gap:10px}
.rv-donut__item{display:flex;align-items:center;gap:8px}
.rv-donut__dot{width:12px;height:12px;border-radius:4px;flex-shrink:0}
.rv-donut__item span{color:#475569;font-size:.85rem;font-weight:600}
.rv-donut__item strong{margin-left:auto;color:#0f172a;font-size:.85rem;font-weight:800}

/* Area */
.rv-area{overflow-x:auto;position:relative}
.rv-area__wrap{position:relative}
.rv-area__svg{width:100%;height:180px}
.rv-area__labels{display:flex;justify-content:space-between;margin-top:6px}
.rv-area__labels span{color:#94a3b8;font-size:.72rem;font-weight:600;min-width:18px;text-align:center;transition:color .15s}
.rv-area__labels--active{color:#6366f1!important;font-weight:800!important}

/* Tooltip */
.rv-area__tooltip{position:absolute;z-index:10;pointer-events:none;padding:10px 14px;border-radius:14px;background:rgba(15,23,42,.88);backdrop-filter:blur(12px);border:1px solid rgba(99,102,241,.3);box-shadow:0 8px 32px rgba(99,102,241,.25),0 0 0 1px rgba(255,255,255,.06) inset;transform:translateX(-50%);min-width:130px;text-align:center}
.rv-area__tooltip-date{color:#a5b4fc;font-size:.72rem;font-weight:700;margin-bottom:3px;letter-spacing:.03em}
.rv-area__tooltip-rev{color:#fff;font-size:1.05rem;font-weight:900;background:linear-gradient(90deg,#a5b4fc,#67e8f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.rv-area__tooltip-orders{color:#94a3b8;font-size:.72rem;font-weight:600;margin-top:2px}

/* Tooltip transition */
.rv-tip-enter-active,.rv-tip-leave-active{transition:opacity .15s,transform .15s}
.rv-tip-enter-from,.rv-tip-leave-to{opacity:0;transform:translateX(-50%) translateY(6px)}

/* Pulse animation */
@keyframes areaPulse{0%{r:10;opacity:.15}100%{r:20;opacity:0}}
.rv-area__pulse{animation:areaPulse 1.2s ease-out infinite}

/* Products */
.rv-products{display:flex;flex-direction:column;gap:8px}
.rv-products__item{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:14px;background:#f8fafc;border:1px solid rgba(22,82,197,.04);transition:background .15s}
.rv-products__item:hover{background:#eff6ff}
.rv-products__rank{width:26px;height:26px;display:inline-grid;place-items:center;border-radius:8px;background:#e2e8f0;color:#475569;font-size:.75rem;font-weight:900;flex-shrink:0}
.rv-products__rank.--gold{background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#fff}
.rv-products__rank.--silver{background:linear-gradient(135deg,#94a3b8,#64748b);color:#fff}
.rv-products__rank.--bronze{background:linear-gradient(135deg,#d97706,#92400e);color:#fff}
.rv-products__info{flex:1;min-width:0}
.rv-products__info strong{display:block;color:#0f172a;font-size:.82rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rv-products__bar{height:5px;margin:5px 0 3px;border-radius:99px;background:#e2e8f0;overflow:hidden}
.rv-products__bar span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#1652c5,#14b8a6);transition:width .5s ease}
.rv-products__info small{color:#94a3b8;font-size:.72rem;font-weight:600}
.rv-products__more{width:100%;margin-top:12px;padding:10px;border:1px solid rgba(22,82,197,.15);border-radius:12px;background:linear-gradient(135deg,#f0f4ff,#f8fafc);color:#1652c5;font-weight:800;font-size:.82rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;transition:all .2s}
.rv-products__more:hover{background:linear-gradient(135deg,#e0e7ff,#eff6ff);border-color:rgba(22,82,197,.3);box-shadow:0 4px 16px rgba(22,82,197,.1)}

/* Daily stats cards (fill chart empty space) */
.rv-daily-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:18px}
.rv-daily-stats__card{display:flex;align-items:center;gap:12px;padding:16px;border-radius:16px;background:linear-gradient(135deg,#f0f4ff 0%,#f8fafc 100%);border:1px solid rgba(99,102,241,.08);transition:transform .2s,box-shadow .2s}
.rv-daily-stats__card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(99,102,241,.1)}
.rv-daily-stats__card>i{width:42px;height:42px;display:grid;place-items:center;border-radius:12px;background:linear-gradient(135deg,#6366f1,#3b82f6);color:#fff;font-size:1.1rem;flex-shrink:0}
.rv-daily-stats__card span{display:block;color:#64748b;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.rv-daily-stats__card strong{display:block;color:#0f172a;font-size:1rem;font-weight:900;margin-top:1px}
.rv-daily-stats__card small{display:block;color:#6366f1;font-size:.72rem;font-weight:700;margin-top:1px}

.rv-empty{border:1px dashed rgba(22,82,197,.2);border-radius:16px;padding:20px;color:#64748b;background:#f8fafc;font-weight:700;text-align:center}
.rv-empty--err{color:#dc2626;border-color:rgba(220,38,38,.2);background:#fef2f2}

@media(max-width:992px){.rv-row{grid-template-columns:1fr}.rv-kpi{grid-template-columns:repeat(2,1fr)}.rv-daily-stats{grid-template-columns:1fr}}
@media(max-width:576px){.rv-kpi{grid-template-columns:1fr}}
</style>
