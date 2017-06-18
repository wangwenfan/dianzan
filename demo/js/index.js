/**
 * Created by wangwf on 17-6-17.
 */
$(function(){
//定义文本
    const paragraph = $('#paragraph');
    const paragraphText = paragraph.text();
    const paragraphLength = paragraph.text().length;
//定义文章长度
    const maxParagraphLength = 80;
//定义全文按钮
    const paragraphExtender = $('#paragraphExtender');
    var toggleFullParagraph = false;

//定义全文按钮
    if (paragraphLength < maxParagraphLength) {
        paragraphExtender.hide();
    } else {
        paragraph.html(paragraphText.substring(0, maxParagraphLength) + '...');
        paragraphExtender.click(function(){
            if (toggleFullParagraph) {
                toggleFullParagraph = false;
                paragraphExtender.html('显示全文');
                paragraph.html(paragraphText.substring(0, maxParagraphLength) + '...');
            } else {
                toggleFullParagraph = true;
                paragraphExtender.html('收起');
                paragraph.html(paragraphText);
            }
        });
    };
    const menuBtn = $('.actionToggle');
    menuBtn.click(function () {
        var menu=$(this).parents('.toolbar').children('div').children('.actionMenu');
        menu.toggleClass('active');
    });

    //点赞
    $(".btnLike").on('click',function () {
        var self = $(this);
        var likeTxt = self.parents('.weui_cell_bd').children('.liketext');
        var user_id = self.attr('data-userid');
        var post_id = self.parents(".weui_cell").attr('data-postid');
        var zanstate1='icon-38',zanstate0='icon-65';
        $.post("controller.php",{user_id:user_id,post_id:post_id},function (data) {
            console.log(data);
            if(data.status == 1){
                likeTxt.append(',<span class="nickname zan'+user_id+'">王思聪</span>');
            }else{
                likeTxt.children('.zan'+user_id).remove();
                zanstate1='icon-65';
                zanstate0='icon-38';
            }
            self.children().removeClass(zanstate0).addClass(zanstate1);
            self.parent().removeClass('active');
        },'json');
    });

});