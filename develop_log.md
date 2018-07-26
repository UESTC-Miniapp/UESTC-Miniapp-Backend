# UESTClife小程序后端
这并不是多么严谨的readme，大概更像是遇到的坑吧。
##登录
首先是登录时候提交的表单，
表单一般分为form-data和x-www-urlencoded。
学校登录的那个网站是
```
http://idas.uestc.edu.cn/authserver/login
```
表单格式是后者(x-www-urlencoded)，
但是我被chrome坑了，
因为chrome上写的是Form Data。
如果提交的表单是form-data，
会导致响应500错误。
如果登录成功，应该会响应302，
然后会有几个Set-Cookie，建议全部保留。
实际上主要起作用的cookie就是CASTGC，
即使只有这一个，依然是登录状态。
或者也可以使用route和JSESSIONID_ids1(有时候是ids2之类的)，
这样登录的话会导致一个302跳转，
然后再次获取CASTGC。  
登录其他网站的时候用到的cookie是iPlanetDirectoryPro，
虽然我不太理解为什么单独做一个用来跨站，
而且这个cookie还不能用来直接登录idas，
总之很坑爹就是了。
所以我建议保留所有的cookie，
大多数爬虫没有自动302跳转功能（至少curl没有），
跨站的时候要从idas获取一个地址用于跳转（比如portal的话是ticket），
跳转的那个页面会响应Set-Cookie，最后再跳转回来。
这一系列操作，如果爬虫不支持自动跳转的话，简直就是灾难。
不同的域名提交不同的cookie也需要注意，
cookieJar还是很有必要的（然而curl就是没有，你气不气？好怀念Python）。
### 验证码
大概说一下验证码。
是否需要验证码，可以通过一下链接来确定
```
http://idas.uestc.edu.cn/authserver/needCaptcha.html
```
需要两个参数（不含引号）

- "username" = 学号
- "_" = 当前时间（unix时间戳，单位毫秒），对，就是一个下划线，你没看错

返回的内容一般有两种（不含引号）

- "true\n" = 需要验证码，可以让用户先手动登录一次再退出
- "false\n" = 不需要验证码，可以直接登录

如果不想太麻烦用户的话，可以直接获取验证码输入就行。
链接是
```
http://idas.uestc.edu.cn/authserver/captcha.html
```

这个连接是写在js里面的，
有时候（就是点换一张的时候）需要加一个参数ts，
至于值是多少我就不确定了，自己去看js代码吧。