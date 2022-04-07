<!--{template meta}-->
<link rel="stylesheet" media="all" href="{SITE_URL}static/css/widescreen/css/greenlogin.css" />
<body class="no-padding reader-black-font" style="    background-color: #f1f1f1;">

<div class="sign">
    <div class="logo"><a href="/"><img src="{$setting['site_logo']}" alt="Logo"></a></div>
    <div class="main">
      


<h4 class="title">
  <div class="normal-title">
    <a class="active" href="{url user/getpass}">邮箱找回</a>
    <b>·</b>
    <a id="js-sign-up-btn" class="" href="{url user/getphonepass}">手机号找回</a>
  </div>
</h4>
<div class="js-sign-up-container">
  <form class="new_user" name="getpassform"  action="{url user/getpass}" method="post">
         <input type="hidden" class="form-control"  name="authcode"  value="{$authcode}" />  
  
      <div class="input-prepend">
      <input type="text" class="" id="email" autocomplete="off" name="email" placeholder="邮箱"/>
      <i class="fa fa-envelope"></i>
    </div>
      <div class="input-prepend">
         <img class="pull-right" src="{url user/code}" onclick="javascript:updatecode();" id="verifycode">

                    
             <input type="text" class="" autocomplete="off" id="code" name="code" placeholder="输入验证码后点击按钮发送邮箱验证" onblur="check_code();"/>
			  <i class="fa fa fa-get-pocket"></i>
			  </div>	
			    <div class="input-prepend  no-radius security-up-code js-security-number ">
        <input type="text" id="seccode_verify" name="seccode_verify"  placeholder="邮箱验证码" >
      <i class="fa fa-get-pocket"></i>
     <a  id="testbtn" onclick="getemailcode()" class="btn-up-resend js-send-code-button" href="javascript:;">发送验证码</a>
      <div><div class="captcha"><input  name="captcha[validation][challenge]" autocomplete="off" type="hidden" value="a025bd170ffad53e107ed83f4fb7e916"> <input name="captcha[validation][gt]" autocomplete="off" type="hidden" value="a10ea6a23a441db3d956598988dff3c4"> <input name="captcha[validation][validate]" autocomplete="off" type="hidden" value=""> <input name="captcha[validation][seccode]" autocomplete="off" type="hidden" value=""> <input name="captcha[id]" autocomplete="off" type="hidden" value="1ad70a1e-f61a-4665-a1bd-f29a05cabe89"> <div id="geetest-area" class="geetest"><div class="gt_input"><input class="geetest_challenge" type="hidden" name="geetest_challenge"><input class="geetest_validate" type="hidden" name="geetest_validate"><input class="geetest_seccode" type="hidden" name="geetest_seccode"></div></div></div></div>
    </div>
    <div class="input-prepend ">
      <input placeholder="设置密码" type="password" id="password" name="password" autocomplete="off" maxlength="20">
      <i class="fa fa-lock"></i>
    </div>

      <div class="input-prepend">
      <input placeholder="确认密码" type="password" id="repassword" name="repassword" autocomplete="off" maxlength="20">
      <i class="fa fa-lock"></i>
    </div>					 
    <input type="submit" name="submit" id="regsubmit"  value="提交" class="sign-up-button">
   
</form>
<!--{template openlogin}-->
</div>

    </div>
  </div>





