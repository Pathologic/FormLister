# FormLister

Работа с формами

```
[!FormLister?
&to=`me@test.test`
&formid=`messageForm`
&addPlaceholders=`document`
&formTpl=`@CODE:
<h2>Записаться на кастинг</h2>
<form id="messageForm" method="post">
<input name="formid" type="hidden" value="messageForm" />
<input name="pageId" type="hidden" value="[+doc.id+]">
<div class="form-group">
<label>Фамилия, имя, отчество</label>
<input  name="name" type="text" class="form-control" value="[+name.value+]"/>
[+name.error+]
</div>
<div class="form-group">
<label>Возраст</label>
<input  name="age" type="text" class="form-control" value="[+age.value+]"/>
[+age.error+]
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
<label>Телефон для связи</label>
<input name="phone" type="text" class="form-control" value="[+phone.value+]"/>
[+phone.error+]         
</div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
<label>E-mail</label>
    <input name="email" type="text" class="form-control" value="[+email.value+]" />
[+email.error+]
</div>
    </div>
</div>
<div class="form-group">
<label>О себе</label>
<textarea name="message" class="form-control" rows="7">[+message.value+]</textarea>
[+message.error+]
<button type="submit">Отправить</button>
</div>
</form>
[+form.messages+]
`
&errorTpl=`@CODE:<div><small>[+message+]</small></div>`
&rules=`{
"name":{
    "required":"Введите ваше имя",
    "minLength":{
        "params":[3],
        "message":"Имя должно быть больше трех символов"
    }
},
"age":{
    "required":"Укажите ваш возраст",
    "numeric":"Неверно указан возраст"
},
"phone":{
    "required":"Укажите номер телефона",
    "phone":"Неверно указан номер"
},
"email":{
    "required":"Укажите ваш E-mail",
    "email":"Неверно указан E-mail"
},
"message":{
    "required":"Расскажите о себе",
    "minLength":{
        "params":[300],
        "message":"Расскажите подробнее, не менее 300 символов"
    }
}
}
`
&successTpl=`@CODE:
<p>Имя: [+name+]</p>
<p>Возраст: [+age+]</p>
<p>Телефон: [+phone+]</p>
<p>E-mail: [+email+]</p>
<hr>
<p>[+message+]</p>
<hr>
`
!]
```