# TestCase.md - Testcase Theo Component FE PharmaGo

Ngày lập: 05/05/2026  
Phạm vi: `C:\Users\ACER\Downloads\Pharmacity FE\src\components` và các API Laravel liên quan trong `C:\Users\ACER\Pharmacity`.  
Cách đọc: mỗi nhóm bên dưới tương ứng một component/màn hình thật trong FE. Các dòng `GUI-*` kiểm tra giao diện, các dòng `FUNC-*` kiểm tra hành vi, API, validation và dữ liệu.

## Quy Ước

| Cột | Ý nghĩa |
|---|---|
| Test Case ID | Mã testcase theo component. |
| Mô tả | Thành phần/chức năng cần kiểm tra. |
| Hành động | Các bước tester thực hiện trên giao diện hoặc API. |
| Điều kiện tiên quyết | Dữ liệu/tài khoản/trạng thái cần có trước khi test. |
| Kết quả mong đợi | Kết quả đúng theo hệ thống PharmaGo hiện tại. |
| Kết quả thực tế | Tester điền sau khi chạy. |
| Vòng 1, Vòng 2 | Dùng cho test lần đầu và retest sau khi sửa lỗi. |

## Component: `Client/DangNhap/index.vue` - Trang Đăng Nhập

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-DN-001 | Hiển thị form đăng nhập | Mở `/login` | Chưa đăng nhập | Hiển thị tiêu đề `Đăng Nhập`, ô email/SĐT, ô mật khẩu, nút đăng nhập, link đăng ký, link về trang chủ | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DN-002 | Nút hiện/ẩn mật khẩu | Nhập mật khẩu, click icon mắt | Có form đăng nhập | Mật khẩu đổi giữa dạng che và dạng text, icon đổi đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DN-003 | Modal quên mật khẩu | Click `Quên mật khẩu?` | Chưa đăng nhập | Modal mở, có email, mã xác minh, mật khẩu mới, xác nhận mật khẩu, nút gửi mã/đặt lại | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DN-004 | Modal chọn kênh nhân viên | Đăng nhập tài khoản nhân viên hợp lệ không chọn kênh | Nhân viên active | Chỉ sau khi login nhân viên mới hiện modal `Chọn kênh đăng nhập` gồm `Hệ thống`, `Tại quầy` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DN-005 | Modal xác minh email khách | Đăng nhập khách chưa xác minh email | Khách chưa verify email | Modal nhập mã 6 số hiển thị, có nút `Xác minh và đăng nhập`, `Gửi lại mã` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DN-001 | Khách đăng nhập thành công | Nhập email/SĐT + mật khẩu khách đã xác minh, bấm đăng nhập | Customer đã verify | Lưu token customer, chuyển về redirect hoặc trang chủ | - | Chưa chạy | - | - | Chưa chạy | - | - | API `/login` |
| FUNC-DN-002 | Admin đăng nhập thành công | Nhập tài khoản admin, bấm đăng nhập | Admin active | Lưu token admin, chuyển tới `/thong-ke`, không tạo phiên làm việc 8 tiếng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DN-003 | Nhân viên chọn kênh hệ thống | Login staff, chọn `Hệ thống` | Không có nhân viên khác đang active kênh hệ thống | Lưu token staff, `login_channel=he_thong`, chuyển `/thong-ke`, bắt đầu tính giờ làm | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DN-004 | Nhân viên chọn kênh tại quầy | Login staff, chọn `Tại quầy` | Không có nhân viên khác đang active tại quầy | Lưu token staff, `login_channel=tai_quay`, chuyển `/ban-tai-quay` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DN-005 | Chặn đăng nhập khi hệ thống có người | Staff B chọn `Hệ thống` khi Staff A đang active | Có phiên `he_thong` chưa hết hạn | Hiện thông báo `Hiện tại hệ thống đang có người đăng nhập`, không tạo token mới | - | Chưa chạy | - | - | Chưa chạy | - | - | HTTP 409 |
| FUNC-DN-006 | Chặn đăng nhập khi tại quầy có người | Staff B chọn `Tại quầy` khi Staff A đang active | Có phiên `tai_quay` chưa hết hạn | Hiện lỗi đang có người đăng nhập tại quầy, không vào trang bán hàng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DN-007 | Sai mật khẩu | Nhập đúng tài khoản sai mật khẩu | Có tài khoản | Hiển thị lỗi đăng nhập, không lưu token | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DN-008 | Quên mật khẩu thành công | Nhập email, nhận mã, nhập mật khẩu mới và xác nhận | Email khách tồn tại | Mật khẩu đổi, có thể đăng nhập bằng mật khẩu mới | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/DangKy/index.vue` - Trang Đăng Ký

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-DK-001 | Hiển thị form đăng ký | Mở `/register` | Chưa đăng nhập | Có họ tên, SĐT, email, địa chỉ, mật khẩu, xác nhận mật khẩu, nút tạo tài khoản | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DK-002 | Validation tại từng trường | Focus rồi blur trường rỗng/sai | Chưa đăng nhập | Trường lỗi có class invalid và thông báo lỗi bên dưới | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DK-003 | Modal xác minh email sau đăng ký | Đăng ký hợp lệ | Email/SĐT chưa tồn tại | Modal nhập mã xác minh mở, hiển thị email nhận mã, nút gửi lại mã | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DK-004 | Nút điều hướng | Click `Đã có tài khoản?` và `Quay về trang chủ` | Chưa đăng nhập | Điều hướng đúng tới `/login` và `/` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DK-001 | Đăng ký khách hàng thành công | Điền dữ liệu hợp lệ và bấm tạo tài khoản | SĐT/email mới | Tạo khách hàng trạng thái chờ xác minh, gửi mã Gmail, tạo mã giảm 10% đơn đầu | - | Chưa chạy | - | - | Chưa chạy | - | - | API `/register` |
| FUNC-DK-002 | Đăng ký thiếu dữ liệu | Bỏ trống từng field bắt buộc | Chưa đăng nhập | Không gửi đăng ký hoặc API trả 422, không tạo khách | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DK-003 | SĐT sai định dạng | Nhập SĐT không đủ 10 số/ký tự chữ | Chưa đăng nhập | Hiển thị lỗi SĐT, không tạo tài khoản | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DK-004 | Email sai định dạng | Nhập email không hợp lệ/không `.com` | Chưa đăng nhập | Hiển thị lỗi email, không tạo tài khoản | - | Chưa chạy | - | - | Chưa chạy | - | - | Theo rule BE |
| FUNC-DK-005 | Mật khẩu xác nhận không khớp | Nhập 2 mật khẩu khác nhau | Chưa đăng nhập | Hiển thị lỗi xác nhận mật khẩu, không tạo tài khoản | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DK-006 | Email/SĐT trùng | Đăng ký bằng email hoặc SĐT đã tồn tại | Có khách hàng cũ | API trả 422, không tạo khách và không tạo mã welcome mới | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DK-007 | Xác minh email đúng mã | Nhập mã 6 số hợp lệ | Có mã verification chưa hết hạn | Khách được active, lưu token customer, chuyển vào hệ thống | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DK-008 | Gửi lại mã xác minh | Click `Gửi lại mã` | Đã đăng ký nhưng chưa verify | Gửi mã mới, hiển thị thông báo thành công, không reset form sai | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/TrangChu/index.vue` - Trang Chủ Khách Hàng

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-TC-001 | Slider hero | Mở `/`, bấm trái/phải/dot | Có dữ liệu slide | Slide đổi đúng, nội dung không đè lên nhau, ảnh/nền không bị vỡ | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TC-002 | Nhóm vấn đề sức khỏe | Click từng thẻ vấn đề sức khỏe | Có cấu hình category | Điều hướng sang danh mục đúng slug, active category đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TC-003 | Card sản phẩm trang chủ | Quan sát danh sách sản phẩm | Có catalog API | Ảnh, tên, mã, giá, giá gốc, badge khuyến mãi, nút xem chi tiết/mua ngay hiển thị đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TC-004 | Modal chi tiết sản phẩm | Click `Xem chi tiết` | Có sản phẩm | Modal hiện mô tả, đơn vị, giá, tồn kho, liều lượng, đóng được | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TC-001 | Load catalog thành công | Mở trang chủ | API `/catalog/thuocs` có dữ liệu | Sản phẩm được nhóm đúng theo section, không hiện lỗi | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TC-002 | API catalog lỗi | Giả lập API lỗi | Không cần dữ liệu | Hiển thị alert lỗi, không trắng trang | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TC-003 | Mua ngay sản phẩm thường | Click `Mua ngay` sản phẩm không kê đơn | Thuốc còn tồn | Sản phẩm được thêm/chọn trong giỏ và chuyển tới `/thanh-toan` hoặc `/gio-hang` theo logic hiện tại | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TC-004 | Sản phẩm kê đơn | Click mua sản phẩm kê đơn | Thuốc có nhãn kê đơn | Không mua trực tiếp; chuyển/tạo tư vấn dược sĩ hoặc thông báo cần tư vấn | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/TimKiem/index.vue` - Tìm Kiếm Thuốc

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-TK-001 | Ô tìm kiếm | Mở `/tim-kiem` | Không cần đăng nhập | Có input, nút tìm, quick keyword, trạng thái loading/empty | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TK-002 | Quick keyword | Click một từ khóa gợi ý | Có quick keyword | Keyword được đưa vào ô tìm kiếm và tự chạy tìm/lọc | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TK-003 | Modal sản phẩm từ kết quả | Click card hoặc `Xem chi tiết` | Có kết quả | Modal mở đúng, có chọn đơn vị nếu nhiều đơn vị, mô tả có nút xem thêm/thu gọn | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TK-001 | Tìm có kết quả | Nhập tên/mã thuốc tồn tại và bấm tìm | Catalog có thuốc phù hợp | Danh sách chỉ hiện thuốc phù hợp, URL/query không lỗi | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TK-002 | Tìm không có kết quả | Nhập từ khóa không tồn tại | Không cần dữ liệu | Hiển thị `Chưa tìm thấy thuốc phù hợp` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TK-003 | Thêm sản phẩm vào giỏ | Click nút thêm/mua trong kết quả | Thuốc còn tồn, không kê đơn | Giỏ tăng số lượng, đúng đơn vị đã chọn | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TK-004 | Tư vấn sản phẩm kê đơn | Click sản phẩm kê đơn | Thuốc có nhãn kê đơn | Không thêm giỏ trực tiếp, mở luồng hỗ trợ/tư vấn | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/DanhMuc/index.vue` - Danh Mục Thuốc

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-DM-001 | Hiển thị danh mục theo slug | Mở `/danh-muc/{sectionSlug}` | Có thuốc thuộc nhóm | Tiêu đề, breadcrumb/filter, card thuốc hiển thị đúng nhóm | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DM-002 | Card sản phẩm | Quan sát card | Có sản phẩm | Không vỡ ảnh, tên dài không tràn, giá/đơn vị/tồn kho rõ ràng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DM-001 | Đổi category con | Click category con | Có category slug | Danh sách reload đúng category, không mất layout | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DM-002 | Thêm/mua sản phẩm từ danh mục | Click nút thêm/mua | Thuốc còn tồn | Sản phẩm thêm vào giỏ đúng đơn vị và giá hiện hành | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DM-003 | Slug không có sản phẩm | Mở slug không có dữ liệu | Không có thuốc phù hợp | Hiển thị empty state, không crash route | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/ProductVariantSelect.vue` - Chọn Phân Loại Sản Phẩm

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-PVS-001 | Dropdown phân loại | Click control chọn đơn vị | Sản phẩm có nhiều đơn vị | Menu mở ngay dưới nút, không bị che, có option đang active | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-PVS-001 | Chọn option | Click một option khác | Có options | Emit value mới, giá/tồn/đơn vị ở parent cập nhật đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-PVS-002 | Click ngoài dropdown | Mở dropdown rồi click ngoài | Có menu đang mở | Menu đóng, không đổi value | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/GioHang/index.vue` - Giỏ Hàng

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-GH-001 | Giỏ hàng rỗng | Mở `/gio-hang` khi cart rỗng | Không có item | Hiển thị icon giỏ trống, mô tả, nút `Tiếp tục mua sắm` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-GH-002 | Bảng giỏ hàng có sản phẩm | Mở giỏ có item | Có sản phẩm trong local cart | Hiển thị checkbox, ảnh, tên, phân loại, đơn giá, số lượng, nút xóa | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-GH-003 | Tổng tiền | Quan sát card đơn hàng | Có item được chọn | Tạm tính, giảm giá mã, VAT 10%, tổng tiền format VND đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GH-001 | Chọn/bỏ chọn sản phẩm | Click checkbox từng item | Có nhiều item | Item selected đổi đúng, tổng tiền chỉ tính item selected | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GH-002 | Chọn tất cả | Click checkbox header | Có item còn hàng và hết hàng | Chọn tất cả item còn hàng, item hết hàng không được chọn | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GH-003 | Tăng/giảm số lượng | Bấm `+`/`-` | Item còn hàng | Số lượng min 1, tổng tiền cập nhật, localStorage lưu đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GH-004 | Xóa item | Click icon thùng rác | Có item | Item bị xóa khỏi cart, tổng tiền/badge giỏ cập nhật | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GH-005 | Xóa tất cả | Click `Xóa tất cả` | Cart có nhiều item | Cart rỗng, chuyển sang empty state | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GH-006 | Mua hàng | Click `Mua hàng` | Có item selected | Nếu đã login đi `/thanh-toan`, nếu chưa login bị router đưa `/login?redirect=/thanh-toan` | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/ThanhToan/index.vue` - Thanh Toán

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-TT-001 | Danh sách sản phẩm checkout | Mở `/thanh-toan` | Customer login, có item selected | Hiển thị ảnh, tên, phân loại, số lượng, giá từng sản phẩm | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TT-002 | Modal thông tin sản phẩm | Click `Thông tin sản phẩm` | Có sản phẩm checkout | Modal hiện ảnh đúng, mã thuốc, loại, đơn vị, NSX, tồn kho, mô tả; đóng không để lại backdrop | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TT-003 | Khu vực địa chỉ | Quan sát và chọn địa chỉ | Customer có địa chỉ | Card địa chỉ hiển thị họ tên/SĐT/địa chỉ, chọn được địa chỉ mặc định | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TT-004 | Phương thức thanh toán | Chọn từng radio COD/MoMo/ZaloPay/ATM/QR | Customer login | Logo/text đúng; PayOS hiển thị `QR`, COD hiển thị COD | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TT-005 | Modal chọn khuyến mãi | Click `Chọn mã` | Customer login | Modal có mã đủ điều kiện và chưa đủ điều kiện, nút áp dụng, lý do không áp dụng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TT-006 | Điểm thưởng | Quan sát box điểm thưởng | Customer có điểm | Hiển thị số điểm, checkbox dùng điểm nếu đủ; thông tin 1000 điểm giảm 10000 rõ ràng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-001 | Chặn vào checkout khi chưa login | Mở `/thanh-toan` khi guest | Chưa đăng nhập | Router chuyển `/login?redirect=/thanh-toan` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-002 | Đặt hàng COD | Chọn COD, tick điều khoản, bấm đặt hàng | Customer login, item còn tồn | Tạo hóa đơn `cho_xac_nhan`, thanh toán `tien_mat`, chưa cộng điểm tới khi nhân viên xác nhận | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-003 | Đặt hàng PayOS/QR | Chọn QR, bấm đặt hàng | PayOS configured | Tạo đơn PayOS pending, redirect checkout_url PayOS | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-004 | Hủy PayOS | Ở trang PayOS bấm hủy/quay về cancel_url | Có đơn PayOS pending | FE gọi cancel API, đơn tạm bị xóa, không có thông báo hủy, không cộng điểm | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-005 | Tiếp tục PayOS khi đóng trang | Đóng PayOS, vào lịch sử đơn bấm tiếp tục | Đơn PayOS pending chưa hủy | Mở lại checkout_url cũ, không tạo đơn trùng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-006 | Áp dụng mã giảm giá hợp lệ | Mở modal mã, bấm áp dụng | Có mã đủ điều kiện | Mã hiện ở summary, giảm giá cập nhật đúng, có thể bỏ mã | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-007 | Mã chưa đủ điều kiện | Chọn mã chưa đủ điều kiện | Tổng đơn thấp hoặc mã không hợp lệ | Không áp dụng, hiển thị lý do/disable nút áp dụng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-008 | Dùng điểm thưởng | Tick dùng điểm | Customer >= 1000 điểm, đơn đủ tiền | Mỗi 1000 điểm giảm 10000, tổng tiền cập nhật; điểm chỉ xử lý theo trạng thái thanh toán/xác nhận | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-009 | Điểm dự kiến không tính VAT | Đặt đơn có VAT | Tổng sản phẩm biết trước | Điểm dự kiến = tổng tiền sản phẩm / 1000, không tính VAT | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TT-010 | Điều khoản | Bỏ tick điều khoản rồi bấm đặt hàng | Có item | Nút đặt hàng disabled, không gửi API | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/Profile/index.vue` - Tài Khoản Khách Hàng

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-PF-001 | Menu tài khoản | Mở `/tai-khoan` | Customer login | Các tab thông tin, địa chỉ, lịch sử đơn hàng, thông báo hiển thị đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-PF-002 | Form thông tin cá nhân | Mở tab thông tin | Customer login | Tên, SĐT, email, địa chỉ, ngày sinh, giới tính, avatar hiển thị đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-PF-003 | Lịch sử đơn hàng | Mở tab lịch sử đơn | Có đơn | Card đơn hiển thị mã, ngày, số sản phẩm, trạng thái, tổng tiền, nút mua lại/xem sản phẩm/xóa | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-PF-004 | Nút tiếp tục thanh toán | Quan sát đơn PayOS | Có đơn PayOS pending và paid | Chỉ đơn PayOS pending hiện `Tiếp tục thanh toán`; đơn paid/đã xác nhận không hiện | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-PF-005 | Thông báo khách hàng | Mở tab thông báo | Có thông báo đơn hàng/ưu đãi/sức khỏe | Danh sách nhóm đúng, nút đã đọc/mark read hoạt động | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-PF-001 | Cập nhật profile | Sửa thông tin và lưu | Customer login | API `/profile` cập nhật, dữ liệu mới hiển thị sau reload | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-PF-002 | Upload avatar | Chọn ảnh jpg/png/webp <=2MB | Customer login | Avatar upload thành công, preview và header cập nhật | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-PF-003 | Thêm/sửa/xóa địa chỉ | Thao tác trong tab địa chỉ | Customer login | CRUD địa chỉ đúng chủ tài khoản, địa chỉ mặc định hoạt động | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-PF-004 | Mua lại đơn hàng | Click `Mua lại` | Đơn có sản phẩm còn bán | Sản phẩm được thêm lại vào giỏ với đơn vị/giá hiện tại | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-PF-005 | Xem sản phẩm đã mua | Click `Xem sản phẩm đã mua` | Đơn có chi tiết | Modal/list sản phẩm hiển thị đủ tên, đơn vị, số lượng, giá | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-PF-006 | Thông báo theo trạng thái PayOS | Sync orders | Có đơn PayOS pending/paid/confirmed/canceled | Pending: chưa thanh toán; paid: thanh toán thành công chờ xác nhận; confirmed: đặt hàng thành công; canceled: không hiện thông báo | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Client/HoTroChatbox/index.vue` - Chat Hỗ Trợ Khách

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-HTC-001 | Nút mở chatbox | Mở trang khách hàng | Không cần login | Nút chat nổi hiển thị, không che nút mua hàng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-HTC-002 | Khung chat | Click mở chat | Không cần login | Khung chat có danh sách tin, ô nhập, nút gửi, trạng thái loading/lỗi | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HTC-001 | Guest gửi tin | Guest nhập tin và gửi | Chưa login | Tạo guest_session_id, tạo hội thoại guest, tin hiển thị trong chatbox | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HTC-002 | Customer gửi tin | Customer login gửi tin | Customer login | Tin gắn với khách hàng, staff nhìn thấy hội thoại | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HTC-003 | Guest disconnect | Đóng/ngắt hội thoại guest | Guest có hội thoại | API xóa hội thoại và tin nhắn guest, không xóa hội thoại customer | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/Dashboard/index.vue` - Thống Kê

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-DB-001 | Hero dashboard | Mở `/thong-ke` | Staff/admin hệ thống | Hiển thị tên người dùng, ngày, KPI, không vỡ layout | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DB-002 | Bảng hiệu suất hôm nay | Quan sát panel | Có dữ liệu nhân viên | Danh sách nhân viên, giờ làm, doanh thu hôm nay hiển thị đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-DB-003 | Biểu đồ tuần/tháng | Bấm chuyển tuần, xem xếp hạng tháng | Có hóa đơn | Biểu đồ đổi dữ liệu, nút xem thêm/thu gọn hoạt động | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DB-001 | Load dashboard | GET `/dashboard/staff-performance` | Staff/admin hệ thống | Trả doanh thu hôm nay/tuần/tháng, nhân viên đăng nhập, ranking | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DB-002 | Doanh thu theo phiên đăng nhập | Xem ngày có nhân viên login | Có login log và hóa đơn | Doanh thu chỉ tính cho nhân viên có phiên phù hợp ngày đó | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-DB-003 | Chặn khách vào dashboard | Customer/guest mở `/thong-ke` | Không phải nhân viên hệ thống | Guest về `/login`, customer về `/` | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/HoaDon/index.vue` - Quản Lý Hóa Đơn

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-HD-001 | Trang hóa đơn | Mở `/hoa-dons` | Staff/admin hệ thống | Hiển thị thống kê tổng đơn, doanh thu hệ thống, doanh thu tại quầy, VAT | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-HD-002 | Tìm kiếm hóa đơn | Nhập keyword mã đơn/SĐT/khách/nhân viên | Có hóa đơn | Loading hiển thị, kết quả lọc đúng, nút xóa lọc reset danh sách | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-HD-003 | Nút xác nhận/từ chối | Quan sát đơn chờ xác nhận | Có đơn `cho_xac_nhan` | Đơn chờ có nút xác nhận và từ chối; đơn hoàn thành/từ chối không hiện sai nút | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-HD-004 | Modal từ chối đơn | Click từ chối | Có đơn chờ xác nhận | Modal nhập lý do mở, lý do bắt buộc, đóng được | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HD-001 | Xác nhận đơn COD | Click xác nhận đơn COD | Đơn COD đã thanh toán tiền mặt/chờ xác nhận | Đơn chuyển `da_xac_nhan`, gửi email, cộng điểm khách, doanh thu hệ thống tính cho nhân viên | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HD-002 | Chặn xác nhận PayOS pending | Click xác nhận đơn PayOS chưa paid | Đơn PayOS pending | Hiển thị lỗi chưa thanh toán thành công, không cộng điểm, không gửi email thành công | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HD-003 | Xác nhận PayOS paid | Click xác nhận đơn PayOS paid | Webhook PayOS đã paid | Đơn xác nhận, email hiển thị phương thức thanh toán `QR` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HD-004 | Từ chối đơn | Nhập lý do và gửi | Đơn chờ xác nhận | Đơn chuyển từ chối, gửi email kèm lý do, hoàn tồn kho/điểm/mã nếu có | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HD-005 | Xem thêm hóa đơn | Click `Xem thêm` | Có nhiều hóa đơn | Tăng số dòng hiển thị, không gọi trùng dữ liệu sai | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/BanTaiQuay/index.vue` - Bán Tại Quầy

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-TQ-001 | Màn hình bán tại quầy | Mở `/ban-tai-quay` | Staff kênh `tai_quay` | Có tìm thuốc, danh sách sản phẩm, giỏ tại quầy, ô SĐT khách, tổng tiền, phương thức thanh toán | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TQ-002 | Ô SĐT khách hàng | Nhập số điện thoại | Staff tại quầy | Input và nút `Áp dụng` nằm đúng layout, không bị ô nhỏ/lệch | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TQ-003 | Giỏ tại quầy scroll | Thêm nhiều sản phẩm | Có nhiều item | Khu vực giỏ có thể cuộn riêng, không cần kéo hết danh sách thuốc mới thấy thanh toán | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TQ-004 | Sản phẩm tại quầy | Quan sát card thuốc | Có catalog | Card có ảnh, mã, tên, NSX/nhãn, tồn, giá, select đơn vị, nút thêm | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TQ-001 | Chặn sai kênh | Nhân viên hệ thống mở `/ban-tai-quay` | Staff login `he_thong` | Router chuyển `/thong-ke`, API tại quầy trả 403 nếu gọi trực tiếp | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TQ-002 | Tìm thuốc | Nhập keyword, bấm tìm | Staff tại quầy | Danh sách lọc đúng, lỗi API hiện trong alert | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TQ-003 | Thêm vào giỏ | Chọn đơn vị, click `+` | Thuốc còn tồn | Item thêm vào giỏ, nếu trùng thuốc/đơn vị thì tăng số lượng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TQ-004 | Áp dụng SĐT tích điểm | Nhập SĐT khách tồn tại | Customer tồn tại | Hiển thị khách đã chọn, điểm hiện có, tạo customer_token | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TQ-005 | Không tìm thấy khách | Nhập SĐT không tồn tại | Staff tại quầy | Hiển thị lỗi, không chọn nhầm khách, vẫn có thể bán nếu bỏ số | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TQ-006 | Thanh toán tiền mặt | Giỏ có item, chọn tiền mặt, bấm thanh toán | Staff tại quầy | Tạo hóa đơn `TQ`, `kenh_ban=tai_quay`, thanh toán `tien_mat`, trạng thái hoàn thành | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TQ-007 | Tích điểm tại quầy | Thanh toán có khách đã áp dụng SĐT | Customer có token | Điểm cộng ngay theo tổng tiền sản phẩm, không tính VAT | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TQ-008 | Dùng điểm tại quầy | Tick dùng điểm | Khách đủ 1000 điểm | Giảm 10000 mỗi 1000 điểm, trừ điểm ngay khi hóa đơn hoàn thành | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/NhanVien/index.vue` - Quản Lý Nhân Viên

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-NV-001 | Danh sách nhân viên | Mở `/nhan-viens` | Admin hệ thống | Bảng nhân viên, search, nút thêm/sửa/xóa/đổi mật khẩu hiển thị đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-NV-002 | Modal thêm/sửa nhân viên | Click thêm hoặc sửa | Admin hệ thống | Modal có thông tin tài khoản, vai trò, bằng cấp, trạng thái, profile | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-NV-003 | Modal lịch sử ra vào | Click vào dòng nhân viên, trừ nút hành động | Có nhân viên | Modal mở, có lịch chọn ngày/tháng/năm, tổng phiên, tổng giờ làm | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-NV-004 | Modal đơn bán theo ngày | Click dòng ngày/phiên trong lịch sử | Có đơn ngày đó | Modal hiện các đơn nhân viên bán và sản phẩm trong đơn | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-NV-001 | Chỉ admin vào được | Staff mở `/nhan-viens` | Staff hệ thống | Router chuyển `/thong-ke`; API admin trả 403 nếu gọi trực tiếp | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-NV-002 | Tạo nhân viên | Điền form thêm nhân viên và lưu | Admin, vai trò/bằng cấp tồn tại | Tạo nhân viên, tạo thông tin nhân viên, SĐT dùng login | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-NV-003 | Sửa nhân viên | Sửa họ tên/SĐT/email/vai trò/trạng thái | Admin | Lưu đúng, không cho trùng SĐT/email | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-NV-004 | Đổi mật khẩu | Nhập mật khẩu mới | Admin | Mật khẩu được hash, login bằng mật khẩu mới thành công | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-NV-005 | Xóa nhân viên | Click xóa và xác nhận | Admin | Xóa hoặc báo ràng buộc rõ ràng, không xóa nhầm dữ liệu hóa đơn | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-NV-006 | Lịch sử làm việc 8 tiếng | Xem phiên nhân viên quên logout | Có session quá 8h | Thời gian checkout tự chốt sau 8h, số giờ làm tối đa 8h | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/TonKho/index.vue` - Tồn Kho Và Lô Thuốc

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-KHO-001 | Trang tồn kho | Mở `/ton-kho` | Staff/admin hệ thống | Hiển thị thuốc, lô thuốc, phiếu nhập, cảnh báo tồn kho/hết hạn | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-KHO-002 | Cảnh báo lô gần hết hạn | Quan sát khu cảnh báo | Có lô hết hạn trong 30 ngày | Hiển thị số lô, thuốc, hạn sử dụng, số ngày còn lại | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-KHO-003 | Modal cập nhật lô | Click cập nhật lô | Có lô thuốc | Không có ô `Số lượng nhập thêm`; có số lô, NSX/HSD, giá nhập, tồn hiện tại | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-KHO-004 | Modal tạo phiếu nhập | Click tạo phiếu nhập | Có nhà sản xuất/thuốc | Form thêm nhiều lô, đơn vị nhập, số lượng nhập gốc, giá nhập, tổng tiền | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-KHO-001 | Load tồn kho | Mở trang | Staff/admin | Gọi thuốc, lô, phiếu nhập, cảnh báo; lỗi API hiện alert | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-KHO-002 | Tạo phiếu nhập tăng tồn | Tạo phiếu nhập có chi tiết lô | Thuốc/NSX tồn tại | Lô mới được tạo, tồn kho tăng theo quy đổi đơn vị | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-KHO-003 | Không tăng tồn bằng cập nhật lô | Cố gửi số lượng trong cập nhật lô | Lô tồn tại | API trả 422/prohibited, số lượng tồn không đổi | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-KHO-004 | Hạn dùng sai | Nhập HSD <= ngày sản xuất | Tạo/sửa lô | Hiển thị lỗi `Hạn sử dụng phải sau ngày sản xuất`, không lưu | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-KHO-005 | Cảnh báo mặc định | Gọi alerts không truyền days | Có lô sắp hết hạn | Mặc định cảnh báo lô hết hạn trong 30 ngày và tồn thấp <=20 | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/Thuoc/index.vue` - Quản Lý Thuốc

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-TH-001 | Danh sách thuốc | Mở `/thuocs` | Staff/admin hệ thống | Bảng/card thuốc có mã, tên, ảnh, đơn vị, giá, trạng thái, tồn | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TH-002 | Modal thêm/sửa thuốc | Click thêm/sửa | Staff/admin | Form có tên, mã, NSX, giá, đơn vị, quy cách, mô tả, ảnh, trạng thái | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-TH-003 | Ảnh thuốc fallback | Thuốc không có ảnh | Có thuốc thiếu ảnh | Hiển thị icon/fallback, không hiện ảnh lỗi | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TH-001 | Tạo thuốc | Điền form và lưu | Có NSX | Tạo thuốc, mã thuốc không trùng, ảnh upload nếu có | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TH-002 | Cập nhật thuốc | Sửa thuốc và lưu | Thuốc tồn tại | Dữ liệu mới hiển thị ở admin và catalog public | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TH-003 | Xóa thuốc | Click xóa | Thuốc tồn tại | Xóa hoặc báo ràng buộc nếu đã có lô/đơn, không crash | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-TH-004 | Cập nhật trạng thái | Đổi trạng thái còn bán/ngừng bán | Thuốc tồn tại | Catalog chỉ bán đúng thuốc còn bán | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/GiaKhuyenMai/index.vue` - Giá Và Khuyến Mãi

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-GKM-001 | Trang giá/khuyến mãi | Mở `/gia-khuyen-mai` | Staff/admin | Có search, metric, bảng giá thuốc, nút cập nhật giá, tạo khuyến mãi, tạo mã giảm giá | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-GKM-002 | Modal cập nhật giá | Click `Cập nhật giá` | Có thuốc | Modal có giá bán mới, nút lưu/đóng, loading khi lưu | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-GKM-003 | Modal khuyến mãi thuốc | Click tạo/sửa khuyến mãi | Có thuốc | Form thuốc áp dụng, mã nội bộ, tên, nhãn, mô tả, loại, giá trị, ngày, trạng thái | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-GKM-004 | Modal mã giảm giá | Click tạo/sửa mã giảm giá | Staff/admin | Form mã, tên, mô tả, loại, giá trị, đơn tối thiểu, giới hạn, ngày, trạng thái | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GKM-001 | Cập nhật giá | Nhập giá >=1000 và lưu | Thuốc tồn tại | Giá cập nhật ở bảng giá và catalog | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GKM-002 | Giá không hợp lệ | Nhập giá <1000 hoặc rỗng | Thuốc tồn tại | Hiển thị lỗi, không lưu | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GKM-003 | Tạo khuyến mãi thuốc | Điền form hợp lệ | Thuốc tồn tại | Khuyến mãi active áp vào giá bán catalog đúng loại giảm | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GKM-004 | Ngày khuyến mãi sai | Kết thúc trước bắt đầu | Staff/admin | API trả 422, modal giữ dữ liệu để sửa | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GKM-005 | Tạo mã giảm giá | Điền form hợp lệ | Staff/admin | Mã lưu, khách đủ điều kiện thấy ở checkout | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-GKM-006 | Xóa khuyến mãi/mã giảm giá | Click xóa trong danh sách | Có item | Xóa thành công, danh sách reload, catalog/checkout không còn áp dụng | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/KhachHang/index.vue` - Quản Lý Khách Hàng

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-KHAD-001 | Danh sách khách hàng | Mở `/khach-hangs` | Staff/admin hệ thống | Bảng khách, SĐT, email, điểm, trạng thái hiển thị đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-KHAD-002 | Tìm khách hàng | Nhập tên/SĐT/email | Có khách phù hợp | Kết quả lọc đúng, empty state nếu không có | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-KHAD-001 | Xem chi tiết khách | Click một khách | Có khách | Hiển thị thông tin khách và lịch sử đơn đã mua | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-KHAD-002 | Cập nhật khách | Sửa thông tin khách nếu UI/API hỗ trợ | Có khách | Validate SĐT/email trùng, lưu dữ liệu hợp lệ | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-KHAD-003 | Xóa khách | Click xóa nếu UI/API hỗ trợ | Có khách | Xóa hoặc báo ràng buộc đơn hàng rõ ràng | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component: `Admin/HoTroKhachHang/index.vue` - Hỗ Trợ Khách Hàng

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-HTAD-001 | Danh sách hội thoại | Mở `/ho-tro-khach-hang` | Staff/admin hệ thống | Có danh sách hội thoại, tên khách/guest, trạng thái, thời gian tin cuối | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-HTAD-002 | Khung chat staff | Chọn hội thoại | Có hội thoại | Tin nhắn hiện theo chiều đúng, ô nhập và nút gửi rõ ràng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HTAD-001 | Staff gửi tin | Nhập nội dung và gửi | Có hội thoại mở | Tin lưu vào hội thoại, khách thấy được khi reload chatbox | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HTAD-002 | Đóng hội thoại | Click đóng | Hội thoại đang mở | Trạng thái chuyển đóng, không cho gửi tiếp nếu logic yêu cầu | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HTAD-003 | Xóa hội thoại | Click xóa | Có hội thoại | Hội thoại bị xóa khỏi danh sách, không xóa nhầm hội thoại khác | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component Layout: `CustomerSiteHeader.vue`

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-HDR-001 | Header khách hàng | Mở trang chủ | Không cần login | Logo, danh mục, search, thông báo, giỏ hàng, user menu hiển thị đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-HDR-002 | Mega menu danh mục | Click `Danh mục` | Có danh mục | Mega menu mở/đóng, click category điều hướng đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HDR-001 | Tìm kiếm từ header | Nhập keyword và submit | Không cần login | Chuyển `/tim-kiem` kèm keyword, lưu lịch sử tìm gần đây | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HDR-002 | Menu tài khoản | Click user menu | Có/không login | Guest thấy đăng nhập; customer thấy profile/địa chỉ/lịch sử/thông báo/đăng xuất | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-HDR-003 | Logout | Click đăng xuất | Đang login | Xóa token/session local, chuyển về trang phù hợp, BE logout nếu có token | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Component Layout: `AppSidebar.vue`, `AppHeader.vue`, `AppTopStrip.vue`

| Test Case ID | Mô tả | Hành động | Điều kiện tiên quyết | Kết quả mong đợi | Kết quả thực tế | Vòng 1 - Trạng thái | Vòng 1 - Ngày kiểm tra | Vòng 1 - Người kiểm tra | Vòng 2 - Trạng thái | Vòng 2 - Ngày kiểm tra | Vòng 2 - Người kiểm tra | Chú thích |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| GUI-LAY-001 | Sidebar quản trị | Đăng nhập hệ thống | Staff/admin | Menu đúng theo quyền và kênh, item active đúng route | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-LAY-002 | Header quản trị | Quan sát AppHeader | Staff/admin | Search nhanh, thông tin user, nút đăng xuất hiển thị đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| GUI-LAY-003 | TopStrip thông báo | Quan sát AppTopStrip | Staff/admin | Menu đơn chờ, chat, tồn kho gần hết/hết hạn hiển thị badge đúng | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-LAY-001 | Điều hướng sidebar theo kênh | Click từng menu | Staff `he_thong` hoặc `tai_quay` | Kênh hệ thống không vào quầy nếu sai; kênh tại quầy bị giữ ở `/ban-tai-quay` | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-LAY-002 | Mở thông báo hóa đơn | Click thông báo đơn chờ | Có đơn chờ xác nhận | Điều hướng tới `/hoa-dons` hoặc mở đúng khu vực hóa đơn | - | Chưa chạy | - | - | Chưa chạy | - | - | |
| FUNC-LAY-003 | Mở cảnh báo tồn kho | Click cảnh báo tồn kho | Có lô sắp hết hạn/tồn thấp | Điều hướng tới `/ton-kho`, cảnh báo tương ứng dễ nhìn | - | Chưa chạy | - | - | Chưa chạy | - | - | |

## Checklist Hồi Quy Theo Component Chính

| Nhóm | Lệnh/luồng cần chạy |
|---|---|
| Backend | `php artisan test` tại `C:\Users\ACER\Pharmacity` |
| Frontend | `npm.cmd run build` tại `C:\Users\ACER\Downloads\Pharmacity FE` |
| Auth | Đăng ký -> xác minh email -> đăng nhập khách; đăng nhập admin; đăng nhập staff hệ thống; đăng nhập staff tại quầy |
| Checkout | COD -> nhân viên xác nhận; QR PayOS -> pending -> tiếp tục thanh toán; QR PayOS -> hủy; QR PayOS -> webhook paid -> xác nhận |
| Điểm thưởng | 1.000đ sản phẩm = 1 điểm, không tính VAT; 1.000 điểm giảm 10.000đ |
| Email | COD hiển thị `Tiền mặt`, PayOS/QR hiển thị `QR` |
| Tồn kho | Tạo phiếu nhập tăng tồn; cập nhật lô không được nhập thêm; cảnh báo HSD mặc định 30 ngày |
