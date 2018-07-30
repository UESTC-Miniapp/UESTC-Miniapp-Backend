# UESTClife小程序后端
这并不是readme，而是灌水用的日记。
请不要指望在此找到任何有价值的内容。
## 2018-07-30
目前还是决定用这个URL来检测登录
```
http://eams.uestc.edu.cn/eams/home!submenus.action?menu.id=
```
发生302表示cookie失效，
如果是200则表示成功。
当然在此之前还是先处理下如何登录的问题。
PHP的CURL虽然支持302自动跳转，
但是导致的问题是header部分会全部串到一个字符串，
而显然我目前写的url库并不支持这样的处理，
所以我决定在目前不更新url库的情况下，
手动处理302。
这样的好处当然是能够处理同名但是不同域的cookie，
比如route和JSESSIONID。
对，就是这么恶心。
我大概会在某些时候写一个类似requests那样的库，
并且支持session对象，
用cookieJar的方式保存cookie等等。
但那是后话了，目前这个虽然不怎么好用，
但我个人觉得还是可以的。
---
最近学了一点正则，
修改了`url.php`中处理header字符串的方式。
---
又出问题了，关于确认token有效的php。
学校似乎加了防御，总之就是我用了这个URL做验证
```
http://idas.uestc.edu.cn/authserver/index.do
```
判断方式是302/200，
200=有效，302=无效。
然而现在发生的情况是会302跳转到某位置，然后再跳回来。
好像就是为了验证爬虫有没有302跳转能力？我擦，搞笑呢？
但是没办法，毕竟这不是我写的，我也改不了。
好歹改起来不算麻烦，把get模式设置为自动跳转，
最后判断一下网页标题就OK。
---
回到eams。
网站有个三级域的cookie是`iPlanetDirectoryPro`。
我个人是感觉这玩意没有什么卵用，
登录idas之后，如果浏览器没有提交这么个cookie的话，
服务器会继续Set-Cookie，
但是这个cookie似乎并不参与验证，
只是丢失的话会通过302再Set回来。
最坑的是，服务器似乎也并不保存这么个玩意，
因为每次Set-Cookie的值都不一样......  
当然话是这么说，该加上去的还是加上去吧。
---
重新理一下eams的登录获取cookie的过程。  
首先eams有三个cookie，其中一个是三级域名
- `iPlanetDirectoryPro`（这个是三级域名的）
- `JSESSIONID`
- `sto-id-20480`

具体过程就是，  
- 请求`http://eams.uestc.edu.cn/eams/home!submenus.action`
- 获得cookie:`sto-id-20480`
- 跳转`http://idas.uestc.edu.cn/authserver/login?service=http%3A%2F%2Feams.uestc.edu.cn%2Feams%2Fhome%21submenus.action`
- 提交idas的cookie，登录idas
- 获得cookie:`JSESSIONID_ids1`
(注意这里会设置一个新的cookie，建议入库)
- 跳转`http://eams.uestc.edu.cn/eams/home!submenus.action?ticket=xxxxx`
(需要注意的是这里ticket应该是用于验证，跳转速度一定要快，不然会500错误，
至于多快我就不知道了，反正尽量快吧)
- 跳转`http://eams.uestc.edu.cn/eams/home!submenus.action;jsessionid=xxxxx`
(同上，这里的jsessionid大概也是用于验证)
- 状态码200，获得cookie:`JSESSIONID`

不过理论上来说，这里还没完，
最后一步跳转的页面显示是重复登录的情况，然后要点击继续。
然而那个点击此处，不出以外的话，其实是和请求的URL一样的。
这里不想揣测学校后台是个什么逻辑，
总之按照步骤最终获取`JSESSINID`就可以进入登录状态了。  
还有一件事就是，
如果在请求第二个URL的时候没有附上`iPlanetDirectoryPro`，
那么这个页面会重新Set-Cookie，而且值一般是不同的。
当然有时候即使附上了，依然会有Set-Cookie。
反正我是看不到教务系统的操作了。  
同时在第二个URL时，
一般会修改idas域的cookie`JSESSIONID_ids1`的值。  
如果只是获取`JSESSIONID`，
那么在倒数第二个URL中就已经给出了。
但是为了方便使用，
建议最后再请求一次来“激活”（？）。

## 2018-07-29
感觉学校的网站，cookie用得十分玄学。
学校使用一个页面来完成登录（idas），
该页面只能生成该页面的cookie，以及一个主域名cookie。
对于新登录的情况，其他页面没有cookie，
这时候会一个302到idas，
把验证方式加到URL中的参数（ticket），然后再302，get，
到一个ticket页面，
同样的URL带参数。这一过程中会设定几个cookie，
ticket最后再302到原来的页面。

##2018-07-27
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




