<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "conn.php";
require_once "func.php";

$us_id = us_id();

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Custom Link Shopee</title>
    <link rel="shortcut icon" href="favicon.png">
    <!-- BEGIN PLUGINS STYLES -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <!-- BEGIN THEME STYLES -->
    <link rel="stylesheet" href="assets/stylesheets/theme.min.css" data-skin="default" />
    <link rel="stylesheet" href="assets/stylesheets/custom.css?v=210920_03" />
    <!-- BEGIN BASE JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>
    <style type="text/css">
        .has-copyable {position: relative;}
        .copy {
            float: right;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: #888c9b;
            text-shadow: none;
            opacity: .5;
        }
        button.copy {
            padding: 0;
            background-color: transparent;
            border: 0;
        }
        .has-copyable .copy {
            display: none;
            margin: 0;
            position: absolute;
            top: 50%;
            right: 0;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1;
            color: #888c9b;
            z-index: 4;
            transform: translate3d(0,-50%,0);
        }
        .has-copyable .copy.show {display: block;}
        .has-copyable .form-control {padding-right: 30px;}
    </style>
</head>

<body>
    <div class="page-inner m-2" style="height: auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="myLargeModalLabel" class="modal-title"> Tạo Link Rút Gọn </h5>
            </div>
            <div class="modal-body">
                <p><strong>Rút gọn link Shopee cho một trang cụ thể trên Shopee</strong></p>
                <div class="form-group mb-2">
                    <label class="control-label pr-1" for="customLink_original_url"><span style="color:#ff4d4f">*</span> Link gốc</label>
                    <input type="text" name="customLink_original_url" id="customLink_original_url" class="form-control" value="" placeholder="Paste page URL here. i.e: https://shopee.vn/m/world-milk-day/">
                </div>
                <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                    Thông số theo dõi
                </button>
                <div class="collapse" id="collapseExample">
                    <div class="form-row mt-2">
                      <div class="form-group col-md-6 mb-2">
                          <label class="control-label pr-1" for="customLink_sub_id1">Sub_id1</label>
                          <input type="text" name="customLink_sub_id1" id="customLink_sub_id1" class="form-control" value="" placeholder="Example: SportShoes">
                      </div>
                      <div class="form-group col-md-6 mb-2">
                          <label class="control-label pr-1" for="customLink_sub_id2">Sub_id2</label>
                          <input type="text" name="customLink_sub_id2" id="customLink_sub_id2" class="form-control" value="" placeholder="Example: InstagramFeed">
                      </div>
                      <div class="form-group col-md-6 mb-2">
                          <label class="control-label pr-1" for="customLink_sub_id3">Sub_id3</label>
                          <input type="text" name="customLink_sub_id3" id="customLink_sub_id3" class="form-control" value="" placeholder="Example: 1212BirthdaySale">
                      </div>
                      <div class="form-group col-md-6 mb-2">
                          <label class="control-label pr-1" for="customLink_sub_id4">Sub_id4 (Do not chance)</label>
                          <input type="text" name="customLink_sub_id4" id="customLink_sub_id4" class="form-control" value="" readonly>
                      </div>
                      <div class="form-group col-12 mb-2">
                          <label class="control-label pr-1" for="customLink_sub_id5">Sub_id15 (Do not chance)</label>
                          <input type="text" name="customLink_sub_id5" id="customLink_sub_id5" class="form-control" value="" readonly>
                      </div>
                    </div>
                    <p>Bạn có thể thêm tham số khác để theo dõi hiệu suất liên kết của mình bằng cách gắn thẻ Sub_Id. Chỉ giá trị chữ và số (a-z, A-Z, 0-9). <br> Nhấp trực tiếp vào "Tạo link" nếu bạn không muốn thêm thông số vào liên kết của mình.</p>
                </div>
                <div class="form-actions pt-2">
                    <button type="button" id="customLink_submit" class="btn btn-primary btn-block" style="background: #ee4d2d;border-color: #ee4d2d;">Tạo link</button>
                </div>
                <div id="customLink_result" class="_d-none mt-3 alert alert-soft-success alert-dismissible text-dark border-success fade show mb-3">
                    <p><strong>Link rút gọn</strong></p>
                    <div class="has-copyable mb-2">
                        <button type="button" class="copy copy1 show" aria-label="copy" data-container="body" data-toggle="popover" data-placement="top" data-content="Chia sẻ liên kết đã sao chéo cho bạn bè hoặc chia sẻ lên mạng xã hội" title="" data-original-title="Sao chép thành công"><span aria-hidden="true"><i class="fas fa-copy"></i></span></button>
                        <input type="text" class="form-control border-primary" id="customLink_result_link" onclick="this.select()" readonly placeholder="Chưa tạo link">
                    </div>
                </div>
                <div id="customLink_stats" class="_d-none mt-3 alert alert-soft-success alert-dismissible text-dark border-success fade show mb-3">
                    <p><strong>Link theo dõi</strong></p>
                    <div class="has-copyable mb-2">
                        <button type="button" class="copy copy2 show" aria-label="copy" data-container="body" data-toggle="popover" data-placement="top" data-content="Theo dõi số click và đơn hàng được tạo ra bởi liên kết rút gọn" title="" data-original-title="Sao chép thành công"><span aria-hidden="true"><i class="fas fa-copy"></i></span></button>
                        <input type="text" class="form-control border-primary" id="customLink_stats_link" onclick="this.select()" readonly placeholder="Chưa tạo link">
                    </div>
                </div>
            </div>
        </div>
        <div class="p-2 mt-3 text-center"><a class="btn btn-subtle-primary mr-1" href="https://www.facebook.com/Bcat95/" target="_blank" rel="nofollow">By Bcat95</a></div>
    </div>
    <script type="text/javascript">
    function copyToClipboard_1029(element) {
        var $temp = $("<input>");
        $("body").append($temp);
        $temp.val($(element).val()).select();
        document.execCommand("copy");
        $temp.remove();
    }

    function referralCopy(){
        $('body').on('click', '.has-copyable .copy.copy1 ', function() {
            copyToClipboard_1029($('#customLink_result_link'));
        });
        $('body').on('click', '.has-copyable .copy.copy2', function() {
            copyToClipboard_1029($('#customLink_stats_link'));
        });
    }

    function createLink(){
        let date_now = Date.now();

        $('#customLink_sub_id4').val('<?=$us_id?>');
        $('#customLink_sub_id5').val(date_now);

        $("body").on("click", "#customLink_submit", function() {
            var $this = $(this);
            var url = $('#customLink_original_url').val();
            if (url == '') {
                alert('Vui lòng điền link hợp hệ');
                return;
            }
            var Sub_id1 = $('#customLink_sub_id1').val();
            var Sub_id2 = $('#customLink_sub_id2').val();
            var Sub_id3 = $('#customLink_sub_id3').val();
            var Sub_id4 = $('#customLink_sub_id4').val();
            var Sub_id5 = $('#customLink_sub_id5').val();

            $.ajax({
                type: 'POST',
                dataType: 'JSON',
                url: 'link.php',
                data: {
                    _saToken: "<?php //=$_SESSION['token']?>",
                    apiAppID: 'demo',
                    apiSecret: 'demo',
                    tp: 'link',
                    link_action: 'short_link',
                    us_id: '<?=$us_id?>',
                    url: url,
                    Sub_id1: Sub_id1,
                    Sub_id2: Sub_id2,
                    Sub_id3: Sub_id3,
                    Sub_id4: Sub_id4,
                    Sub_id5: Sub_id5
                },
                success: function(result) {
                    if (result.success.message) {
                        var data = result.success.message;
                        $('#customLink_result').removeClass('d-none');
                        $('#customLink_result_link').val(data);

                        var stats_link = '';
                        stats_link = '';

                        $('#customLink_stats_link').val(stats_link);
                    }
                }
            });
        });
    }

    $(window).on("load",function(){
        referralCopy();
        createLink()
    });
    </script>
    <!-- BEGIN PLUGINS JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.2.1/js.cookie.min.js"></script><!-- END PLUGINS JS -->
    <!-- BEGIN THEME JS -->
    <script src="assets/javascript/theme.min.js"></script> <!-- END THEME JS -->
    <!-- BEGIN PAGE LEVEL JS --><!-- END PAGE LEVEL JS -->
</body>
</html>