**ĐẠI HỌC DUY TÂN**

**KHOA CÔNG NGHỆ THÔNG TIN**

**--------------✦--------------**

**TÓM TẮT BÁO CÁO**

Tên đề tài:

**XÂY DỰNG HỆ THỐNG QUẢN LÝ NHÀ THUỐC TRỰC TUYẾN PHARMAGO TÍCH HỢP CHATBOT HỖ TRỢ TƯ VẤN, BÁN HÀNG TẠI QUẦY VÀ CỔNG THANH TOÁN PAYOS**

Giảng viên hướng dẫn: **Th.S Nguyễn Phúc Minh Tú**

**Sinh viên/Nhóm thực hiện:** Nhóm 102

| **TT** | **Họ và tên sinh viên** | **Mã số sinh viên** |
| ------ | ----------------------- | ------------------- |
| 1      | Hoàng Xuân Nhật Minh    | 27214328802         |
| 2      | Nguyễn Hoài Bảo         | 28211136483         |
| 3      | Lê Vũ Hoàng Duy         | 27217130412         |
| 4      | Nguyễn Quốc Bảo         | 27211246039         |
| 5      | Tô Đông Khánh           | 27211230439         |

_Đà Nẵng, tháng 05 năm 2026_

**MỤC LỤC**

[1. GIỚI THIỆU](#1-gioi-thieu)

[1.1. Mục đích](#11-muc-dich)

[1.2. Phạm vi](#12-pham-vi)

[1.3. Tham khảo](#13-tham-khao)

[2. TỔNG QUAN DỰ ÁN](#2-tong-quan-du-an)

[2.1. Định nghĩa dự án](#21-dinh-nghia-du-an)

[2.2. Mô tả vấn đề](#22-mo-ta-van-de)

[2.3. Giải pháp đề xuất](#23-giai-phap-de-xuat)

[2.4. Kiến trúc hệ thống](#24-kien-truc-he-thong)

[2.5. Cơ sở dữ liệu chính](#25-co-so-du-lieu-chinh)

[3. KẾ HOẠCH TỔNG THỂ](#3-ke-hoach-tong-the)

[3.1. Định nghĩa Scrum](#31-dinh-nghia-scrum)

[3.2. Kế hoạch tổng thể](#32-ke-hoach-tong-the)

[3.3. Quản lý tổ chức](#33-quan-ly-to-chuc)

[3.4. Rủi ro và quản lý rủi ro](#34-rui-ro-va-quan-ly-rui-ro)

[4. BẢNG CHỨC NĂNG VÀ ĐỘ ƯU TIÊN](#4-bang-chuc-nang-va-do-uu-tien)

[5. KẾT LUẬN](#5-ket-luan)

**LỜI CẢM ƠN**

Điều đầu tiên, cá nhân mỗi sinh viên nói riêng và tập thể nhóm em nói chung xin chân thành cảm ơn Quý Thầy/Cô khoa Công Nghệ Thông Tin trường Đại học Duy Tân đã truyền đạt kiến thức, tạo điều kiện học tập, nghiên cứu và rèn luyện trong suốt thời gian qua.

Đặc biệt, nhóm em xin chân thành cảm ơn giảng viên hướng dẫn Th.S Nguyễn Phúc Minh Tú đã hỗ trợ, định hướng và góp ý trong quá trình nhóm thực hiện đề tài. Những góp ý của Thầy/Cô là cơ sở để nhóm hoàn thiện hệ thống theo hướng thực tế hơn, chú trọng hơn vào nghiệp vụ bán thuốc, quản lý tồn kho, thanh toán và trải nghiệm người dùng.

Mặc dù nhóm đã cố gắng xây dựng hệ thống theo đúng nghiệp vụ đã phân tích, kiến thức và kinh nghiệm thực tế vẫn còn hạn chế nên đề tài không tránh khỏi thiếu sót. Nhóm rất mong nhận được sự góp ý của Quý Thầy/Cô để tiếp tục cải thiện sản phẩm, tối ưu nghiệp vụ và nâng cao chất lượng triển khai.

Một lần nữa, nhóm em xin chân thành cảm ơn!

- **PHẦN MỞ ĐẦU**

  - **Lý do chọn đề tài**

Trong bối cảnh nhu cầu chăm sóc sức khỏe ngày càng tăng, nhà thuốc không chỉ cần bán hàng trực tiếp mà còn phải hỗ trợ khách hàng tra cứu sản phẩm, đặt hàng trực tuyến, thanh toán linh hoạt và nhận tư vấn nhanh chóng. Nếu chỉ quản lý thủ công, nhà thuốc dễ gặp các vấn đề như sai lệch tồn kho, khó kiểm soát hạn dùng của lô thuốc, khó theo dõi doanh thu theo nhân viên, khó chăm sóc khách hàng sau mua và tốn nhiều thời gian khi xử lý đơn hàng.

Đề tài **“Xây dựng hệ thống quản lý nhà thuốc trực tuyến Pharmago tích hợp chatbot hỗ trợ tư vấn, bán hàng tại quầy và cổng thanh toán PayOS”** được thực hiện nhằm xây dựng một nền tảng quản lý tập trung cho hoạt động nhà thuốc. Hệ thống vừa phục vụ khách hàng mua thuốc trực tuyến, vừa phục vụ nhân viên quản lý thuốc, tồn kho, hóa đơn, khách hàng, khuyến mãi, điểm thưởng, hỗ trợ khách hàng và bán tại quầy.

Điểm nổi bật của đề tài là hệ thống không dừng lại ở chức năng bán hàng cơ bản mà còn bổ sung các nghiệp vụ thực tế: xác minh email, quên mật khẩu, địa chỉ nhận hàng, mã giảm giá đơn đầu, điểm thưởng, thanh toán PayOS, đồng bộ trạng thái thanh toán, xác nhận hoặc từ chối đơn hàng, gửi email thông báo, quản lý phiên đăng nhập nhân viên, giới hạn một nhân viên đăng nhập theo từng kênh, bán tại quầy và tách doanh thu giữa kênh hệ thống và kênh tại quầy.

  - **Mục tiêu dự kiến**

- Xây dựng giao diện khách hàng để xem danh mục thuốc, tìm kiếm thuốc, xem chi tiết sản phẩm, thêm vào giỏ hàng và đặt hàng.
- Cho phép khách hàng đăng ký, xác minh email, đăng nhập, quên mật khẩu, cập nhật thông tin cá nhân, quản lý địa chỉ nhận hàng và theo dõi lịch sử đơn hàng.
- Tích hợp chương trình ưu đãi cho khách hàng mới: tạo mã giảm 10% cho đơn đầu tiên sau khi đăng ký thành công.
- Xây dựng hệ thống điểm thưởng: 1.000 đồng tiền sản phẩm tương ứng 1 điểm, đủ 1.000 điểm có thể giảm 10.000 đồng ở lần thanh toán sau.
- Tích hợp PayOS để tạo cổng thanh toán QR, lưu link thanh toán, đồng bộ trạng thái đã thanh toán, hủy hoặc quá hạn.
- Xây dựng trang bán tại quầy cho nhân viên, hỗ trợ khách mua trực tiếp, nhập số điện thoại để tích điểm và tách doanh thu tại quầy.
- Xây dựng khu vực quản trị cho admin và nhân viên: thống kê doanh thu, quản lý hóa đơn, khách hàng, nhân viên, thuốc, tồn kho, lô thuốc, phiếu nhập, giá bán, khuyến mãi và hỗ trợ khách hàng.
- Theo dõi thời gian làm việc của nhân viên: ghi nhận thời gian đăng nhập, đăng xuất, tự động kết thúc phiên sau 8 giờ nếu nhân viên quên đăng xuất.
- Quản lý luồng xác nhận đơn hàng: nhân viên xác nhận đơn thành công hoặc từ chối kèm lý do, hệ thống gửi thông báo/email cho khách hàng.
- Tích hợp chatbot hỗ trợ khách hàng tra cứu thuốc, khuyến mãi, đặt hàng và chuyển tiếp sang nhân viên khi nội dung cần dược sĩ tư vấn.
- Cung cấp dữ liệu mẫu thông qua seeder để có thể khởi tạo hệ thống bằng lệnh `php artisan db:seed`.

  - **Hạng mục**

**Hạng mục công nghệ:**

- Backend: PHP 8.2, Laravel 12, Laravel Sanctum, Eloquent ORM.
- Frontend: Vue 3, Vite, Vue Router, Bootstrap 5, Bootstrap Icons, Font Awesome.
- Cơ sở dữ liệu: MySQL.
- Thanh toán: PayOS, webhook và API kiểm tra trạng thái thanh toán.
- Xác thực: token Sanctum, phân quyền admin/nhân viên/khách hàng, xác minh email, đặt lại mật khẩu.
- Gửi thông báo: email xác nhận đơn hàng, email từ chối đơn hàng, thông báo khách hàng trong hệ thống.
- Dữ liệu mẫu: Laravel Seeder, các file dữ liệu tách theo từng nhóm nghiệp vụ.
- Môi trường phát triển: Windows, Visual Studio Code, Composer, npm, Artisan CLI.
- Quy trình phát triển: SCRUM, chia module theo nghiệp vụ và kiểm thử theo từng chức năng.

**Hạng mục nghiệp vụ:**

- Quản lý thuốc, nhà sản xuất, nhãn thuốc, danh mục, đơn vị bán, đơn vị tồn kho và quy đổi đơn vị.
- Quản lý lô thuốc, hạn sử dụng, tồn kho, phiếu nhập và chi tiết phiếu nhập.
- Quản lý hóa đơn, chi tiết hóa đơn, lịch sử đơn hàng, thanh toán, VAT và doanh thu.
- Quản lý khách hàng, địa chỉ nhận hàng, thông báo, mã giảm giá và điểm thưởng.
- Quản lý nhân viên, vai trò, bằng cấp, thông tin nhân viên, trạng thái hoạt động và lịch sử phiên làm việc.
- Quản lý chương trình khuyến mãi thuốc, tự động loại bỏ khuyến mãi quá hạn khỏi danh sách áp dụng.
- Quản lý hỗ trợ khách hàng thông qua hội thoại, tin nhắn và chatbot.

  - **Ý nghĩa khoa học và thực tiễn**

Hệ thống giúp vận dụng kiến thức lập trình web, cơ sở dữ liệu, phân tích nghiệp vụ và thiết kế giao diện vào một bài toán thực tế trong lĩnh vực nhà thuốc. Về mặt kỹ thuật, đề tài kết hợp Laravel API, Vue SPA, xác thực token, phân quyền, thanh toán trực tuyến, seeder dữ liệu, gửi email và quản lý trạng thái nghiệp vụ.

Về mặt thực tiễn, hệ thống hỗ trợ nhà thuốc giảm thao tác thủ công, hạn chế sai sót trong quản lý tồn kho và hóa đơn, giúp nhân viên xử lý đơn nhanh hơn, giúp khách hàng tra cứu và mua thuốc thuận tiện hơn. Việc tích hợp bán tại quầy và bán trực tuyến trong cùng một hệ thống cũng giúp chủ nhà thuốc theo dõi doanh thu theo từng kênh, từng nhân viên và từng thời điểm.

- **TỔNG QUAN VỀ HỆ THỐNG**

  - **Giới thiệu**

Pharmago là hệ thống quản lý nhà thuốc gồm hai phần chính: backend Laravel cung cấp API và frontend Vue hiển thị giao diện cho khách hàng, nhân viên và admin. Backend chịu trách nhiệm xác thực, phân quyền, xử lý nghiệp vụ, lưu dữ liệu, thanh toán PayOS, gửi email và cung cấp dữ liệu cho các màn hình. Frontend chịu trách nhiệm điều hướng, hiển thị dữ liệu, form nhập liệu, modal, toast, giỏ hàng, checkout, quản trị và bán tại quầy.

Hệ thống được chia thành ba nhóm người dùng:

- **Khách hàng:** xem thuốc, tìm kiếm, mua hàng, thanh toán, quản lý tài khoản, nhận thông báo và chat hỗ trợ.
- **Nhân viên:** xử lý hóa đơn, bán tại quầy, quản lý tồn kho, cập nhật giá/khuyến mãi, hỗ trợ khách hàng và theo dõi doanh thu của phiên làm việc.
- **Admin:** có toàn quyền quản lý nhân viên, vai trò, bằng cấp, khách hàng, thuốc, lô thuốc, hóa đơn, thống kê và dữ liệu hệ thống.

  - **Kiến trúc tổng thể**

Hệ thống vận hành theo mô hình client-server:

- Frontend Vue gọi API qua các file trong `src/api`, lưu token và thông tin phiên bằng `authStorage`, sau đó điều hướng bằng Vue Router.
- Backend Laravel định nghĩa API trong `routes/api.php`, xử lý request qua controller, kiểm soát quyền bằng middleware `auth:sanctum` và `nhan_vien.role`.
- Cơ sở dữ liệu MySQL lưu toàn bộ thông tin thuốc, khách hàng, nhân viên, hóa đơn, thanh toán, khuyến mãi, điểm thưởng và hội thoại hỗ trợ.
- PayOS được tích hợp thông qua `PayosService`, `PayosPaymentSyncService`, webhook và các trường thanh toán trong bảng `thanh_toan`.
- Điểm thưởng được xử lý tập trung bằng `RewardPointService`, chỉ cộng/trừ khi thanh toán đủ điều kiện và tránh xử lý lặp.
- Chatbot hỗ trợ khách hàng dùng `SupportAiService`, ưu tiên trả lời theo dữ liệu thuốc/khuyến mãi nội bộ, sau đó mới dùng AI bên ngoài nếu có cấu hình.

  - **Mô tả vấn đề**

Trong vận hành nhà thuốc, các vấn đề thường gặp gồm:

- Khó kiểm soát chính xác số lượng thuốc khi vừa bán online vừa bán trực tiếp.
- Khó theo dõi thuốc sắp hết hạn nếu chỉ dựa vào ghi chép thủ công.
- Khó xác định doanh thu của từng nhân viên và từng kênh bán hàng.
- Khách hàng dễ bỏ dở thanh toán nếu đóng trang thanh toán QR.
- Đơn hàng PayOS đã thanh toán cần được đồng bộ lại trước khi nhân viên xác nhận.
- Việc áp dụng khuyến mãi quá hạn hoặc mã giảm giá đã dùng có thể gây sai lệch doanh thu.
- Khách hàng cần được thông báo rõ khi đơn chưa thanh toán, đã thanh toán, đã xác nhận hoặc bị từ chối.
- Nhân viên cần giao diện nhanh cho bán tại quầy, không phụ thuộc vào luồng giao hàng như đơn trực tuyến.

  - **Giải pháp đề xuất**

Pharmago giải quyết các vấn đề trên bằng cách:

- Tập trung dữ liệu thuốc, lô thuốc, tồn kho, nhập hàng và bán hàng trong một hệ thống.
- Tách rõ kênh bán **hệ thống** và **tại quầy** để thống kê doanh thu chính xác.
- Tạo hóa đơn trực tuyến với trạng thái thanh toán và trạng thái xử lý riêng biệt.
- Với PayOS, lưu `checkout_url`, `payment_link_id`, `order_code` để khách hàng có thể tiếp tục thanh toán nếu lỡ đóng trang.
- Không cộng điểm thưởng khi đơn chưa thanh toán; chỉ cộng khi thanh toán thành công và đơn đủ điều kiện xử lý.
- Với tiền mặt, điểm thưởng chỉ được xử lý sau khi nhân viên xác nhận đơn thành công.
- Với bán tại quầy, luồng chỉ có thanh toán hoặc hủy, không giữ trạng thái chưa thanh toán kéo dài như đơn online.
- Tự động loại bỏ khuyến mãi thuốc đã quá hạn để không còn hiển thị trên sản phẩm.
- Ghi nhận lịch sử đơn hàng, thông báo khách hàng và email theo từng mốc xử lý đơn.
- Giới hạn phiên đăng nhập nhân viên theo kênh để tránh nhiều người cùng dùng một kênh làm sai lệch doanh thu.

  - **Cơ sở dữ liệu chính**

Các nhóm bảng chính trong hệ thống:

- **Tài khoản và phân quyền:** `users`, `personal_access_tokens`, `nhan_viens`, `vai_tros`, `bang_caps`, `thong_tin_nhan_viens`, `nhan_vien_dang_nhap_logs`.
- **Khách hàng:** `khach_hangs`, `dia_chi_khach_hangs`, `email_verifications`, `password_resets`, `thong_bao_khach_hangs`, `thong_bao_khach_hang_da_docs`.
- **Thuốc và tồn kho:** `thuocs`, `nha_san_xuats`, `lo_thuocs`, `phieu_nhaps`, `chi_tiet_phieu_nhaps`.
- **Bán hàng:** `hoa_dons`, `chi_tiet_hoa_don`, `thanh_toan`, `lich_su_don_hangs`.
- **Khuyến mãi và điểm:** `khuyen_mais`, `ma_giam_gias`, `ma_giam_gia_luot_dungs`.
- **Hỗ trợ khách hàng:** `ho_tro_hoi_thoais`, `ho_tro_tin_nhans`.
- **Bán tại quầy PayOS:** `counter_sale_payos_sessions`.

Mối quan hệ dữ liệu trọng tâm:

- Một khách hàng có nhiều địa chỉ, nhiều hóa đơn, nhiều thông báo và điểm tích lũy.
- Một nhân viên có vai trò, bằng cấp, thông tin chi tiết, phiên đăng nhập và các hóa đơn đã xử lý.
- Một thuốc thuộc nhà sản xuất, có nhiều lô thuốc, có thể có nhiều khuyến mãi, xuất hiện trong chi tiết hóa đơn và chi tiết phiếu nhập.
- Một hóa đơn thuộc khách hàng và nhân viên xử lý, có nhiều chi tiết hóa đơn, một bản ghi thanh toán và nhiều lịch sử trạng thái.
- Một phiếu nhập có nhiều chi tiết phiếu nhập, mỗi chi tiết tạo hoặc cập nhật một lô thuốc để tăng tồn kho.

  - **Các phân hệ chức năng**

**1. Phân hệ khách hàng**

- Đăng ký tài khoản, xác minh email và nhận mã giảm giá đơn đầu tiên.
- Đăng nhập, đăng xuất, quên mật khẩu, đặt lại mật khẩu bằng mã xác minh.
- Cập nhật hồ sơ cá nhân, đổi mật khẩu và quản lý avatar.
- Quản lý địa chỉ nhận hàng, đặt địa chỉ mặc định, kiểm tra số điện thoại không trùng tài khoản khác.
- Xem trang chủ, danh mục thuốc, tìm kiếm thuốc, xem chi tiết thuốc, khuyến mãi và tồn kho.
- Thêm sản phẩm vào giỏ hàng, chọn đơn vị bán, áp dụng mã giảm giá hoặc điểm thưởng.
- Thanh toán bằng tiền mặt hoặc PayOS, xem trạng thái thanh toán và tiếp tục thanh toán nếu đơn PayOS còn pending.
- Xem lịch sử đơn hàng, sản phẩm đã mua, trạng thái xử lý và thông báo liên quan.
- Chat hỗ trợ với hệ thống hoặc nhân viên.

**2. Phân hệ admin/nhân viên hệ thống**

- Đăng nhập vào kênh hệ thống, theo dõi phiên làm việc và tự đăng xuất sau 8 giờ nếu quên đăng xuất.
- Xem dashboard doanh thu, số hóa đơn, doanh thu hệ thống, doanh thu tại quầy và VAT đã thu.
- Quản lý hóa đơn: xem chi tiết, xem sản phẩm trong hóa đơn, xác nhận đơn, từ chối đơn kèm lý do.
- Gửi email/thông báo cho khách khi thanh toán thành công, đơn được xác nhận hoặc bị từ chối.
- Quản lý khách hàng và lịch sử mua hàng.
- Quản lý nhân viên: thêm, cập nhật, tạm khóa, đổi mật khẩu, đổi vai trò, xem thời gian ra vào và doanh số theo ngày.
- Quản lý thuốc: thêm, sửa, xóa, cập nhật ảnh, mô tả, liều lượng, đơn vị bán, đơn vị tồn kho, giá bán và trạng thái.
- Quản lý tồn kho/lô thuốc: xem cảnh báo sắp hết hạn, tồn hiện tại và hạn sử dụng.
- Quản lý phiếu nhập: tạo phiếu nhập, nhập số hóa đơn giấy, ngày nhập, ngày hóa đơn, chi tiết thuốc trong phiếu và tăng tồn kho theo lô.
- Quản lý giá và khuyến mãi: cập nhật giá, tạo/sửa/xóa khuyến mãi thuốc, tự động bỏ khuyến mãi quá hạn.
- Quản lý mã giảm giá, chương trình khách hàng mới và lượt sử dụng mã.
- Trả lời hội thoại hỗ trợ khách hàng.

**3. Phân hệ bán tại quầy**

- Nhân viên đăng nhập kênh tại quầy riêng, không dùng chung phiên với kênh hệ thống.
- Tìm thuốc, chọn đơn vị bán, thêm vào giỏ tại quầy và thanh toán trực tiếp.
- Nhập số điện thoại khách hàng để nhận diện khách và cộng điểm nếu khách đã có tài khoản.
- Thanh toán bằng tiền mặt hoặc PayOS tại quầy.
- Nếu thanh toán tiền mặt, hóa đơn hoàn tất ngay tại quầy.
- Nếu thanh toán PayOS, hệ thống tạo phiên thanh toán QR; nhân viên xác nhận theo trạng thái PayOS hoặc hủy giao dịch.
- Doanh thu tại quầy được tách khỏi doanh thu hệ thống và gắn với nhân viên đang giữ phiên tại quầy.

**4. Phân hệ chatbot và hỗ trợ**

- Khách hàng có thể đặt câu hỏi về sản phẩm, khuyến mãi hoặc nhu cầu mua thuốc.
- Hệ thống tìm sản phẩm liên quan theo dữ liệu thuốc, nhãn, mô tả, nhà sản xuất, khuyến mãi và tồn kho.
- Với câu hỏi ngoài phạm vi hoặc cần tư vấn dược sĩ, hệ thống đánh dấu cần nhân viên hỗ trợ.
- Nhân viên/admin xem danh sách hội thoại, trả lời, đóng hoặc xóa hội thoại.

  - **Luồng nghiệp vụ chính**

**Luồng đăng ký khách hàng:**

1. Khách hàng nhập thông tin đăng ký.
2. Hệ thống kiểm tra dữ liệu, email và số điện thoại.
3. Hệ thống tạo tài khoản khách hàng, gửi mã xác minh email.
4. Khi xác minh thành công, khách hàng có thể đăng nhập.
5. Hệ thống tạo mã khuyến mãi 10% cho đơn đầu tiên của khách hàng.

**Luồng đặt hàng online:**

1. Khách hàng chọn thuốc, đơn vị bán và số lượng.
2. Giỏ hàng tính tiền sản phẩm, giảm giá, VAT và tổng thanh toán.
3. Nếu khách dùng điểm thưởng, hệ thống kiểm tra đủ tối thiểu 1.000 điểm.
4. Khách chọn tiền mặt hoặc PayOS.
5. Nếu PayOS, hệ thống tạo link thanh toán và lưu trạng thái pending.
6. Nếu khách đóng trang PayOS, có thể quay lại lịch sử đơn hàng để bấm tiếp tục thanh toán.
7. Khi PayOS xác nhận thành công, hệ thống cập nhật thanh toán paid và chờ nhân viên xác nhận đơn.
8. Nhân viên xác nhận đơn thành công hoặc từ chối kèm lý do.
9. Hệ thống cập nhật lịch sử đơn hàng, thông báo và email cho khách hàng.

**Luồng bán tại quầy:**

1. Nhân viên đăng nhập kênh tại quầy.
2. Nhân viên tìm thuốc, chọn đơn vị bán và thêm vào giỏ.
3. Nhân viên nhập số điện thoại khách nếu muốn tích điểm.
4. Nhân viên chọn thanh toán tiền mặt hoặc PayOS.
5. Nếu tiền mặt, hệ thống tạo hóa đơn hoàn tất tại quầy và cập nhật tồn kho.
6. Nếu PayOS, hệ thống tạo QR; sau khi thanh toán thành công mới tạo/hoàn tất hóa đơn.
7. Nếu hủy, không tạo hóa đơn bán tại quầy.

**Luồng nhập kho:**

1. Nhân viên tạo phiếu nhập, nhập số hóa đơn giấy, ngày nhập hàng, ngày hóa đơn và thông tin ghi chú nếu có.
2. Nhân viên thêm các dòng thuốc trong phiếu nhập.
3. Mỗi dòng gồm thuốc áp dụng, số lô, ngày sản xuất, hạn sử dụng, đơn vị nhập, số lượng nhập và giá nhập.
4. Khi lưu phiếu, hệ thống tự quy đổi về đơn vị tồn kho và tăng tồn theo lô.
5. Không cho tự nhập thêm số lượng trực tiếp trong form cập nhật lô; tồn kho tăng thông qua phiếu nhập để đảm bảo lịch sử rõ ràng.

**Luồng quản lý phiên nhân viên:**

1. Nhân viên đăng nhập bằng tài khoản được cấp.
2. Hệ thống kiểm tra trạng thái tài khoản, vai trò và kênh đăng nhập.
3. Nếu kênh đã có nhân viên khác đăng nhập, hệ thống báo đang có người đăng nhập.
4. Khi đăng nhập thành công, hệ thống ghi nhận thời gian check in.
5. Khi đăng xuất, hệ thống ghi nhận thời gian check out.
6. Nếu nhân viên quên đăng xuất, sau 8 giờ hệ thống tự kết thúc phiên.

**Bảng chức năng và độ ưu tiên**

| **ID User Story** | **Danh sách User Story** | **Thời gian dự kiến (ngày)** | **Độ ưu tiên** |
| ----------------- | ------------------------ | ---------------------------- | -------------- |
| US 01 | Đăng ký tài khoản khách hàng | 2 | 1 |
| US 02 | Xác minh email khách hàng | 2 | 1 |
| US 03 | Đăng nhập, đăng xuất khách hàng | 2 | 1 |
| US 04 | Quên mật khẩu và đặt lại mật khẩu | 2 | 1 |
| US 05 | Cập nhật thông tin cá nhân khách hàng | 2 | 2 |
| US 06 | Quản lý địa chỉ nhận hàng | 3 | 1 |
| US 07 | Xem trang chủ và danh mục thuốc | 3 | 1 |
| US 08 | Tìm kiếm thuốc theo tên, mã, nhãn và danh mục | 2 | 1 |
| US 09 | Xem chi tiết thuốc, ảnh, mô tả, liều lượng và tồn kho | 2 | 1 |
| US 10 | Thêm sản phẩm vào giỏ hàng và chọn đơn vị bán | 2 | 1 |
| US 11 | Thanh toán đơn hàng online bằng tiền mặt | 2 | 1 |
| US 12 | Thanh toán đơn hàng online bằng PayOS | 4 | 1 |
| US 13 | Tiếp tục thanh toán PayOS khi khách đóng trang thanh toán | 2 | 1 |
| US 14 | Theo dõi lịch sử đơn hàng và sản phẩm đã mua | 2 | 1 |
| US 15 | Nhận thông báo trạng thái đơn hàng | 2 | 2 |
| US 16 | Tạo mã giảm giá 10% cho đơn đầu tiên sau đăng ký | 2 | 1 |
| US 17 | Tích điểm thưởng theo tiền sản phẩm | 2 | 1 |
| US 18 | Sử dụng điểm thưởng khi đủ điều kiện | 2 | 1 |
| US 19 | Chatbot tra cứu thuốc và khuyến mãi | 4 | 2 |
| US 20 | Nhân viên/admin đăng nhập hệ thống | 2 | 1 |
| US 21 | Giới hạn phiên đăng nhập nhân viên theo kênh | 3 | 1 |
| US 22 | Ghi nhận thời gian check in/check out nhân viên | 3 | 1 |
| US 23 | Xem doanh số nhân viên theo ngày làm việc | 3 | 2 |
| US 24 | Thống kê doanh thu hệ thống và tại quầy | 3 | 1 |
| US 25 | Quản lý hóa đơn và xem chi tiết sản phẩm trong hóa đơn | 3 | 1 |
| US 26 | Xác nhận đơn hàng đã thanh toán | 2 | 1 |
| US 27 | Từ chối đơn hàng kèm lý do và gửi email cho khách | 2 | 1 |
| US 28 | Quản lý khách hàng và lịch sử mua hàng | 3 | 2 |
| US 29 | Quản lý nhân viên, vai trò, bằng cấp và trạng thái tài khoản | 4 | 1 |
| US 30 | Đổi mật khẩu nhân viên | 2 | 1 |
| US 31 | Quản lý thuốc, ảnh, mô tả, liều lượng và trạng thái bán | 5 | 1 |
| US 32 | Cập nhật giá bán thuốc | 2 | 1 |
| US 33 | Quản lý khuyến mãi thuốc | 3 | 1 |
| US 34 | Tự động loại bỏ khuyến mãi quá hạn | 2 | 1 |
| US 35 | Quản lý tồn kho và cảnh báo lô thuốc gần hết hạn | 4 | 1 |
| US 36 | Tạo phiếu nhập và tăng tồn kho theo lô | 4 | 1 |
| US 37 | Bán hàng tại quầy bằng tiền mặt | 4 | 1 |
| US 38 | Bán hàng tại quầy bằng PayOS | 4 | 1 |
| US 39 | Nhập số điện thoại khách tại quầy để tích điểm | 2 | 2 |
| US 40 | Hỗ trợ khách hàng qua hội thoại nhân viên | 3 | 2 |
| US 41 | Seeder dữ liệu mẫu cho thuốc, lô thuốc, hóa đơn và khách hàng | 3 | 2 |
| US 42 | Tìm kiếm tổng hệ thống trong khu vực admin | 2 | 2 |

- **KẾ HOẠCH TỔNG THỂ**

  - **Định nghĩa Scrum**

SCRUM là quy trình phát triển phần mềm linh hoạt, chia công việc thành các sprint ngắn để nhóm có thể phân tích, thiết kế, lập trình, kiểm thử và điều chỉnh liên tục. Với đề tài Pharmago, SCRUM phù hợp vì hệ thống gồm nhiều module nghiệp vụ khác nhau và thường xuyên cần thay đổi theo phản hồi thực tế.

Các vai trò trong nhóm được phân chia theo hướng:

- Product Owner: tổng hợp yêu cầu, xác định chức năng ưu tiên và kiểm tra mức độ phù hợp với nghiệp vụ nhà thuốc.
- Scrum Master: theo dõi tiến độ, điều phối công việc và xử lý vướng mắc trong quá trình phát triển.
- Development Team: xây dựng backend, frontend, cơ sở dữ liệu, giao diện, API, seeder và kiểm thử chức năng.

  - **Kế hoạch tổng thể**

Quá trình thực hiện được chia thành các giai đoạn:

- Giai đoạn 1: khảo sát yêu cầu, xác định phạm vi, phân tích tác nhân và nghiệp vụ chính.
- Giai đoạn 2: thiết kế cơ sở dữ liệu, API, giao diện và luồng xử lý.
- Giai đoạn 3: xây dựng chức năng khách hàng: đăng ký, đăng nhập, danh mục thuốc, giỏ hàng, checkout, hồ sơ cá nhân.
- Giai đoạn 4: xây dựng chức năng admin/nhân viên: dashboard, hóa đơn, thuốc, tồn kho, phiếu nhập, nhân viên, khách hàng.
- Giai đoạn 5: tích hợp PayOS, điểm thưởng, mã giảm giá, email thông báo và chatbot hỗ trợ.
- Giai đoạn 6: xây dựng bán tại quầy, tách doanh thu và hoàn thiện kiểm thử.
- Giai đoạn 7: bổ sung dữ liệu mẫu bằng seeder, rà soát lỗi giao diện, Việt hóa thông báo và hoàn thiện báo cáo.

  - **Quản lý tổ chức**

Nhóm thực hiện chia công việc theo từng phân hệ để tránh chồng chéo:

- Thành viên phụ trách backend: thiết kế migration, model, controller, service, middleware, seeder và API.
- Thành viên phụ trách frontend khách hàng: trang chủ, danh mục, giỏ hàng, thanh toán, tài khoản và thông báo.
- Thành viên phụ trách frontend quản trị: dashboard, hóa đơn, khách hàng, nhân viên, thuốc, tồn kho, giá và khuyến mãi.
- Thành viên phụ trách tích hợp: PayOS, email, chatbot, điểm thưởng và xử lý trạng thái đơn hàng.
- Thành viên phụ trách kiểm thử: lập test case, kiểm tra form, modal, toast, validation, luồng thanh toán và dữ liệu.

  - **Rủi ro và quản lý rủi ro**

| **Rủi ro** | **Ảnh hưởng** | **Cách xử lý** |
| ---------- | ------------- | -------------- |
| Sai lệch trạng thái PayOS | Nhân viên không xác nhận được đơn đã thanh toán | Đồng bộ trạng thái PayOS trước khi xác nhận và lưu thông tin payment link |
| Trùng số điện thoại/email | Khách hàng hoặc nhân viên khó đăng nhập đúng tài khoản | Kiểm tra unique ở backend và hiển thị lỗi dưới trường nhập |
| Khuyến mãi quá hạn vẫn áp dụng | Sai giá bán và sai doanh thu | Tự động xóa/bỏ áp dụng khuyến mãi quá hạn khi truy vấn |
| Nhân viên quên đăng xuất | Sai thời gian làm việc và doanh thu theo phiên | Tự động kết thúc phiên sau 8 giờ |
| Tồn kho bị tăng không qua phiếu nhập | Mất lịch sử nhập hàng | Chỉ cho tăng tồn thông qua tạo phiếu nhập |
| Thông báo lỗi chưa Việt hóa | Gây khó hiểu cho người dùng | Chuẩn hóa toast và lỗi dưới từng trường |
| Giao diện tràn chữ | Vỡ layout ở dữ liệu dài | Giới hạn ký tự, dùng ellipsis và ràng buộc kích thước responsive |
| Thiếu dữ liệu mẫu | Khó demo hệ thống | Viết seeder theo từng nhóm dữ liệu |

**KẾT LUẬN**

Trong quá trình thực hiện đề tài, nhóm đã xây dựng được hệ thống Pharmago với các chức năng cốt lõi cho nhà thuốc trực tuyến và nhà thuốc bán trực tiếp. Hệ thống có khả năng phục vụ khách hàng mua thuốc online, nhân viên xử lý đơn hàng, admin quản lý dữ liệu và nhân viên bán tại quầy trong cùng một nền tảng.

**Những công việc đã làm:**

- Xây dựng backend Laravel API với xác thực Sanctum, phân quyền admin/nhân viên/khách hàng.
- Xây dựng frontend Vue với giao diện khách hàng, quản trị và bán tại quầy.
- Hoàn thiện các chức năng khách hàng: đăng ký, đăng nhập, xác minh email, quên mật khẩu, hồ sơ cá nhân, địa chỉ nhận hàng, giỏ hàng, checkout, lịch sử đơn hàng và thông báo.
- Hoàn thiện các chức năng quản trị: dashboard, hóa đơn, khách hàng, nhân viên, thuốc, tồn kho, phiếu nhập, giá, khuyến mãi và hỗ trợ khách hàng.
- Tích hợp PayOS cho thanh toán online và bán tại quầy.
- Xây dựng logic điểm thưởng, mã giảm giá đơn đầu tiên, khuyến mãi thuốc và xử lý khuyến mãi quá hạn.
- Xây dựng cơ chế ghi nhận phiên làm việc nhân viên, giới hạn đăng nhập theo kênh và tính doanh thu theo nhân viên.
- Xây dựng chatbot hỗ trợ khách hàng tra cứu thuốc, khuyến mãi và chuyển tiếp nhân viên khi cần tư vấn.
- Viết seeder dữ liệu mẫu để hỗ trợ khởi tạo và demo hệ thống.
- Rà soát giao diện, modal, toast, validation dưới trường nhập và Việt hóa thông báo.

**Hạn chế:**

- Một số nghiệp vụ nhà thuốc thực tế như quản lý đơn thuốc bác sĩ, kiểm tra tương tác thuốc, quản lý bảo hiểm hoặc kiểm kê định kỳ chưa được triển khai đầy đủ.
- Chatbot hiện ưu tiên tra cứu dữ liệu nội bộ và hỗ trợ cơ bản, chưa thay thế được tư vấn chuyên môn của dược sĩ.
- Hệ thống thanh toán phụ thuộc vào cấu hình PayOS và cần môi trường thật để kiểm thử đầy đủ webhook.
- Báo cáo thống kê hiện tập trung vào doanh thu, hóa đơn và nhân viên, chưa mở rộng sang phân tích sâu hành vi khách hàng.

**Hướng phát triển:**

- Bổ sung quản lý đơn thuốc, dược sĩ tư vấn và cảnh báo tương tác thuốc.
- Hoàn thiện báo cáo nâng cao về sản phẩm bán chạy, tồn kho chậm luân chuyển và hiệu quả khuyến mãi.
- Tích hợp in hóa đơn, mã vạch/QR sản phẩm và kiểm kê kho định kỳ.
- Mở rộng chatbot theo hướng tư vấn có kiểm soát, luôn có cảnh báo cần hỏi dược sĩ với nội dung liên quan điều trị.
- Tối ưu trải nghiệm mobile cho khách hàng và quầy bán hàng.
- Bổ sung kiểm thử tự động cho các luồng quan trọng như thanh toán PayOS, xác nhận đơn hàng, nhập kho và điểm thưởng.
