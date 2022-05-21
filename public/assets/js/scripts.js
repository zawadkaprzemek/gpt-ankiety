$(document).ready(function(){

const QUESTION_PROTOTYPE='<div class="form-group row" data-item="__X__">'+
'<div class="col-12 col-sm-3 col-form-label"><label for="question_answers___name___content" class="required">Odpowiedź __X__</label></div>'+
'<div class="col-11 col-sm-8"><input type="text" id="question_answers___name___content" name="question[answers][__name__][content]" required="required" class="form-control" /></div>'+
'<div class="col-1 text-right"><button class="btn btn-dark btn-sm btn-delete-answer" type="button">Usuń</button></div>'+
'</div>';
let questionForm=$('#questionForm');
if(questionForm.length>0){
    showQuestionTypeFields($('#question_type').val());
    
    showFields($('#question_valueLabels').prop('checked'),$('.value-labels'));
    showFields($('#question_commentOn').prop('checked'),$('.comment'));
    $('#question_commentOn').trigger("change");
    showFields($('#question_middleValueLabel').prop('checked'),$('.middleLabel'));

    if($('#question_middleValueLabel').prop('checked'))
    {
        $('#question_middleValueLabel').parent().parent().parent().removeClass('pt-2');
        $('#question_middleValueLabel').parent().parent().remove();
    }
}





$('#question_type').on('change',function(){
    showQuestionTypeFields($(this).val());
});


function showQuestionTypeFields(type)
{
    switch(type)
    {
        case '1':
            $('.type-2').addClass('d-none');
            $('.type-2 input').prop('required',false).prop('disabled',true);
            $('.type-3').addClass('d-none');
            $('.type-3 input').prop('required',false).prop('disabled',true);
            break;
        case '2':
            $('.type-2').removeClass('d-none');
            $('.type-2 input').prop('required',true).prop('disabled',false);
            $('.type-3').addClass('d-none');
            $('.type-3 input').prop('required',false).prop('disabled',true);
            break;
        case '3':
            $('.type-2').addClass('d-none');
            $('.type-2 input').prop('required',false).prop('disabled',true);
            $('.type-3').removeClass('d-none');
            $('.type-3 input').prop('required',true).prop('disabled',false);
            break;
        default:
            $('.type-2').addClass('d-none');
            $('.type-2 input').prop('required',false).prop('disabled',true);
            $('.type-3').addClass('d-none');
            $('.type-3 input').prop('required',false).prop('disabled',true);
            break;
    }
}





$('#question_valueLabels').on('change',function(){
    showFields($(this).prop('checked'),$('.value-labels'));
});

function showFields(checked,elem)
{
    if(checked)
    {
        elem.removeClass('d-none');
        if(!elem.hasClass('value-labels'))
        {
            elem.find('label').addClass('required');
        }
    }else{
        elem.find('label').removeClass('required');
        elem.addClass('d-none');
    }

    elem.find('input,textarea').prop('required',checked).prop('disabled',!checked);
}

$('#question_commentOn').on('change',function(){
    showFields($(this).prop('checked'),$('.comment'));
});


$('#question_middleValueLabel').on('change',function(){
    showFields($(this).prop('checked'),$('.middleLabel'));
    if($(this).prop('checked'))
    {
        $(this).parent().parent().parent().removeClass('pt-2');
        $(this).parent().parent().remove();
    }
});


$('.add-answer').on('click',function(){
    let list = jQuery(jQuery(this).attr('data-list-selector'));
    let newWidget = QUESTION_PROTOTYPE;
    let counter = $('.answers-list .answer').length;
    newWidget = newWidget.replace(/__name__/g, counter);
    counter++;
    newWidget=newWidget.replaceAll('__X__',counter);
    list.data('widget-counter', counter);
    var newElem = jQuery(list.attr('data-widget-tags')).html(newWidget);
        newElem.appendTo(list);
});


$('body').on('click','.btn-delete-answer',function(){
    let btn =$(this);
    if(confirm('Na pewno chcesz usunąć tą odpowiedź?'))
    {
        let item=btn.parent().parent().data('item');
        if(item!=$('.answer').length)
        {
            let items=$('.answer .row').filter(function() {
                return  $(this).data("item") > item;
            });
            let list = jQuery(jQuery(this).attr('data-list-selector'));
            items.each(function(i,e){
                let curr_item=$(e).data('item');
                let new_item=curr_item-1;
                $(e).data('item',new_item);
                $(e).find('label').text($(e).find('label').text().replace(curr_item,new_item)).prop('for',$(e).find('label').prop('for').replace(new_item,(new_item-1)));;
                $(e).find('input').prop('id',$(e).find('input').prop('id').replace(new_item,(new_item-1))).prop('name',$(e).find('input').prop('name').replace(new_item,(new_item-1)))
                let counter = $('.answers-list .answer').length;
                counter--;
                list.data('widget-counter', counter);
            });
            btn.parent().parent().parent().remove();
        }
    }
});

    
});