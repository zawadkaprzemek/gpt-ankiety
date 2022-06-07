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
    $('#question_middleValueLabel').prop('required',false);
    $('#question_middleValText').prop('required',$('#question_middleValueLabel').prop('checked'));
}

$('#question_commentOn').on('change',function(){
    showFields($(this).prop('checked'),$('.comment'));
});


$('#question_middleValueLabel').on('change',function(){
    showFields($(this).prop('checked'),$('.middleLabel'));
    if($(this).prop('checked'))
    {
        $(this).parent().parent().parent().removeClass('pt-2');
        $(this).parent().parent().addClass('d-none');
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
        }
        btn.parent().parent().parent().remove();
    }
});

    
$('#voteForm input,#voteForm textarea').on('change',function(){
    let val=$(this).val().trim(),
        question=$(this).data('question'),
        url=$('#voteForm').data('save-url'),
        required=$(this).prop('required'),
        type=$(this).attr('type'),
        minlenght=$(this).attr('minlength')
        ;

        $('#question-error-'+question).removeClass('d-block');
    if(required&&(val==''||type=="textarea"&&val.length<minlenght))
    {
        $('#question-error-'+question).addClass('d-block');
        $(this).focus();
        return;
    }
    $.ajax({
        method: 'POST',
        url: url,
        data: JSON.stringify({question:question,value:val}),
        success: function(data, textStatus, xhr) {
            console.log(data);
        },
        complete: function (xhr, textStatus) {
            
        }
    });
})


$("#voteForm").on('submit',function(e){
    let form=$(this);
    $('.question-error').removeClass('d-block');
    let data=$(this).serializeArray(),
    errors =0;
    $(data).each(function(i,elem){
        let question=elem.name.replace('question-','');
        let field=form.find('[data-question="'+question+'"]'),
        required=field.prop('required');
        if(required)
        {
            if(field.length==1 && field.prop('type')=="textarea")
            {
                let minlenght=$(this).attr('minlength');
                if(elem.value=='' || elem.value.length< minlenght)
                {
                    $('#question-error-'+question).addClass('d-block');
                    errors++;
                }
            }else{
                if(elem.value=='')
                {
                    $('#question-error-'+question).addClass('d-block');
                    errors++;
                }
            }
        }
        
    });

    if(errors>0)
    {
        e.preventDefault();
    }
});




$('.polling-box i').on('click',function(){
    let url=$(this).parent().parent().data('open');
    let elem=$(this);
    $.ajax({
        method: 'POST',
        url: url,
        success: function(data, textStatus, xhr) {
            if(data.status=='success'){
                elem.toggleClass('on');
                elem.prop('title',(data.open? 'Włączona' : 'Wyłączona'));
            }
        },
        complete: function (xhr, textStatus) {
            
        }
    });
});



let sortable=$('.sortable');
if(sortable.length>0)
{
    let first=$(sortable).find('.question')[0];
    sortable.attr('data-number',($(first).data('number')-1));
}


function handleDragStart(e) {
    this.style.opacity = '0.4';
    dragSrcEl = this;

  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/html', this.innerHTML);
  }

  function handleDragEnd(e) {
    this.style.opacity = '1';

    items.forEach(function (item) {
      item.classList.remove('over');
    });
  }

  function handleDragOver(e) {
    e.preventDefault();
    return false;
  }

  function handleDragEnter(e) {
    this.classList.add('over');
  }

  function handleDragLeave(e) {
    this.classList.remove('over');
  }

  function handleDrop(e) {
    e.stopPropagation(); // stops the browser from redirecting.
    if (dragSrcEl !== this) {
        dragSrcEl.innerHTML = this.innerHTML;
        this.innerHTML = e.dataTransfer.getData('text/html');
        updatePositions();
    }
    
    //console.log($('.sortable .question').index(this));
    return false;
  }
  
  let items = document.querySelectorAll('.sortable .question');
  items.forEach(function (item) {
    item.addEventListener('dragstart', handleDragStart);
    item.addEventListener('dragover', handleDragOver);
    item.addEventListener('dragenter', handleDragEnter);
    item.addEventListener('dragleave', handleDragLeave);
    item.addEventListener('dragend', handleDragEnd);
    item.addEventListener('drop', handleDrop);
  });


  function updatePositions()
  {
    let items = document.querySelectorAll('.sortable .question');
    items.forEach(function (item) {
        let data_index= $(item).find('.question_header').prop('data-index');
        let index= $('.sortable .question').index(item);
        if(data_index!==index)
        {
            let question=$(item).find('.question_header').data('question');
            let start=parseInt($('.sortable').attr('data-number'));
            let polling =$('.sortable').data('polling');
            let url="/panel/ankieta/"+polling+"/"+question+"/ustaw_pozycje";
            $.ajax({
                method: 'POST',
                url: url,
                data: JSON.stringify({position:index+1}),
                success: function(data, textStatus, xhr) {
                    $(item).find('.question_number').html(data.position+start);
                    $(item).find('.question_header').prop('data-index',index);
                },
                complete: function (xhr, textStatus) {
                    
                }
            });
        }
    });
  }

});



