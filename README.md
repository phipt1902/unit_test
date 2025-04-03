Danh sách Test Cases
1. Kiểm tra xử lý đơn hàng loại A
- Tạo file CSV khi đơn hàng loại A.
- File CSV có đúng định dạng và nội dung.
- Đánh dấu trạng thái đơn hàng là exported.
- Nếu tạo file CSV thất bại, trạng thái đơn hàng phải là export_failed.
2. Kiểm tra xử lý đơn hàng loại B
- Gọi API thành công và dữ liệu trả về >= 50, amount < 100 → processed.
- Gọi API thành công và dữ liệu trả về < 50 hoặc flag = true → pending.
- Gọi API thành công nhưng không khớp điều kiện nào → error.
- API trả về lỗi → api_error.
- API throw exception → api_failure.
3. Kiểm tra xử lý đơn hàng loại C
- Nếu flag = true → completed.
- Nếu flag = false → in_progress.
4. Kiểm tra cập nhật trạng thái vào database
- Nếu amount > 200, priority phải là high.
- Nếu amount ≤ 200, priority phải là low.
- Nếu database throw exception → db_error.
5. Kiểm tra trường hợp ngoại lệ
- Nếu getOrdersByUser throw exception, phải trả về false.
<img width="720" alt="Screen Shot 2025-04-03 at 16 03 30" src="https://github.com/user-attachments/assets/0a016850-229f-4fcb-b340-9fd998a6d390" />
