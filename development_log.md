# UESTClife小程序后端开发日志
这并不是readme，而是灌水用的日记。
请不要指望在此找到任何有价值的内容。
接口文档去看API.md。

## 2018-11-08

学校升级了统一验证页面，现在需要重写eams登录和idas登录。

## 2018-11-05

稍微修改了下接口文档的格式，然后放到网站上。https://ch34k.xyz/docs/UESTC-Miniapp-Backend/API。

## 2018-10-29
把check_token.php干了，
现在当静态文件用，只返回
```json
{
"success":true
}

```
这个后期我会解决，
另外就是下次准备用Guzzle重构，
替换掉使用了url.php的部分。  
原来*http for human*在php也是存在的！！
## 2018-10-28
check_token出现迷之错误，
get请求返回null，
用postman则正常。

## 2018-10-12
用Exception重写了login.php，
有种优雅了许多的错觉。
工作量似乎比我预想的小一些？

顺便跟新了接口文档，
加入status功能。
## 2018-10-10
试一下做个自动部署。

顺便还有那个login接口的status也要搞定，
心累。
## 2018-10-09
再次修复history.php中json字符串错乱。
对php的数组了解不够透彻，
还需要深入学习。  
API更新了注册信息模块，
这里爬取方式。

请求方式为GET，URL是
```URL
http://eams.uestc.edu.cn/eams/registerApply!search.action
```
带一个参数`_`，
参数值为unix时间戳（毫秒）。  
请求得到的字符串为HTML代码，
表格的内容就在第三个`tbody`标签里面，
直接上正则就行。

注册信息开发完成，
貌似这玩意只是当个装饰？
服了老哥了。

## 2018-10-08
修复了history响应中array变object的问题。

以后写完代码还是得测试才行啊。
## 2018-10-07
啊，国庆假期结束啦。

在ecard/info.php里面把名字加上了。
## 2018-09-24
今天大概可以把交易流水搞定吧。
顺便修改下接口文档。  
请求URL为
```URL
http://ecard.uestc.edu.cn/web/guest/personal?
```
方式是POST，带一堆参数：
- `p_p_id=transDtl_WAR_ecardportlet`
- `p_p_lifecycle=0`
- `p_p_state=exclusive`
- `p_p_mode=view`
- `p_p_col_id=column-4`
- `p_p_col_count=1`
- `_transDtl_WAR_ecardportlet_action=dtlmoreview`

都是固定的。  
还带一个表单，完整版如下：
- `_transDtl_WAR_ecardportlet_cur`=页数
- `_transDtl_WAR_ecardportlet_delta`=总页数
- `_transDtl_WAR_ecardportlet_qdate`=查询时间（7|30|60|180）
- `_transDtl_WAR_ecardportlet_qtype`=交易类型（1=充值|2=消费|3=易支付电控）

差不多就这样，另外查第一页不用前面两个。

写完了。  
教务处网站又双叒叕挂了，
现在不好测试，先放着不推了。
## 2018-09-19
更新了.gitignore，
以后有空还是做一下git推送自动部署，
每次ssh感觉还是挺麻烦的。
当然搞定samba然后直接把工程文件放上去就更好了。

三个图表的接口搞定了，
下一步是那个比较麻烦的交易流水。
最近还是懒得做重构，
毕竟能跑就行（笑），
而且把验证放过去还会降低性能。
比较有必要的是login.php的多线程，
可以把登录速度加快点。

靠，出问题了，
不能直接返回过去，
得放到json中的data部分，
靠。  
已解决。
## 2018-09-18
一卡通的登录会经过一个跳转到idas，
然后再跳转回来，
这一过程中会进行Set-Cookie。  
关键是跳转特别快，这就有点坑了。

用postman直接请求会读取到一卡通登录页面，
postman有重定向自动跟随，
但是浏览器打开则会跳转到统一认证，
也就是说这里的跳转是通过js实现的，
把浏览器的js关掉后没有发生跳转也说明了这点。

另外可以确定的一点是，
登录idas之后会发生302跳转到ecard，
链接中有个参数为ticket，
似乎就是用这个参数来实现跨站验证的。  
而且跳转过去的时候一定要快，
否则报500错误。  
但是从请求的结果来看，
十分奇怪。
带ticket的URL请求，
没有任何Set-Cookie，
而浏览器会携带几个cookie。  
我的理解是，
第一次请求的时候设置了cookie，
然后ticket作为一个激活码，
学校后端接到这个激活码后将原来的cookie生效。

不过我又试了下关闭js的时候，
一卡通页面依然可以通过表单的POST请求来登录。
如果这样登录不影响其他cookie，
那么就决定使用这个方式来登录一卡通。
一卡通有三个cookie
- `COOKIE_SUPPORT=true`
- `GUEST_LANGUAGE_ID=zh_CN`
- `JSESSIONID=...`这个应该就是用于验证的了

其中`JSESSIONID`会在请求`http://ecard.uestc.edu.cn/`的时候设置。  
登录时候POST的URL是
```URL
http://ecard.uestc.edu.cn/c/portal/login
```
登录时候提交的表单有三个数据
- `_58_login_type=_58_login_type`
- `_58_login`学号
- `_58_password`密码

如果登录成功的话会302跳转到
```URL
http://ecard.uestc.edu.cn/web/guest/personal
```
并且会设置新的`JSESSIONID`并加上一个`GUEST_LANGUAGE_ID=zh_CN`  

如果登录失败则一般会跳转到
```URL
http://192.168.254.154/web/guest/index?_yktlogin_WAR_ecardportlet_err=1
```
这里做个判断就可以。

再次感觉到重构的必要了，
有必要用上异常处理，
免得返回的内容不准确，
写得像坨shit。

忘了说了，读取一卡通信息的URL是
```URL
http://ecard.uestc.edu.cn/web/guest/personal
```
## 2018-09-17
突然发现似乎要登录一卡通了，
也就是`ecard.uestc.edu.cn`这个域名。  
所以login.php和check_token.php可能要新增代码了。
## 2018-09-16
正式开始写一卡通部分，这一块内容有点多，可能要放缓了。前端部分估计工作量也比较大，今年内应该没法上线。

写一下内容吧，以下几点（突然学起领导的口气了呢）

- 一卡通信息，包括卡号，余额，状态等
- 交易流水，明细
- 消费趋势
- 消费地点
- 充值趋势

最后三点就是“我的活动”里面的那三个图，分别对应三个POST，响应都是json，基本上直接发回去就行，URL都是
```
http://ecard.uestc.edu.cn/web/guest/myactive
```
包含8个链接参数
- `p_p_id=myActive_WAR_ecardportlet`
- `p_p_lifecycle=2`
- `p_p_state=normal`
- `p_p_mode=view`
- `p_p_resource_id`有三个值，
`consumeStat`,`consumeComp`,`dpsStat`，
分别对应三张表的数据。
- `p_p_cacheability=cacheLevelPage`
- `p_p_col_id=column-1`
- `p_p_col_count=1`

以及一个Form-Data
- `_myActive_WAR_ecardportlet_days=30`

前面两个就从页面获取，信息没什么说的，老样子。交易明细是一个HTML table，在后端做解析json再发回。因为学长的需求，就把一卡通部分的接口都放到一个目录下。  

是时候重构一波了，复用验证请求和token部分的代码。  

一卡通信息比较好做，就先做这个。
## 2018-09-11
个人信息部分写完了，
简单测试了一下，貌似没有问题。  
有时间重构一下，
可以复用验证的部分。
## 2018-09-10
教师节，苦逼军训，呵呵。  
准备添加个人信息功能，
URL如下
```
http://eams.uestc.edu.cn/eams/stdDetail.action?_=
```
请求方式为GET，
带一个参数`_`，
参数值为当前时间(unix时间戳，毫秒)。
请求需要cookie，应该只用两个就行
- `JSESSIONID`
- `iPlanetDirectoryPro`

返回的数据主要是HTML的表格，
老样子，正则大法好。
## 2018-09-05
这几天在军训，比较严，没有拿笔记本出来。  
目前考试信息出现bug，
体现为出现空键，
猜测是数组越界。  
目前问题已经解决，并且更新了文档。
## 2018-09-02
现在出了一个问题，
就是我自己的课表总是爬不出来。
经过检测发现应该是POST的表单中ids的问题。  
用袁仁义的账号时`ids=142846`，
而我的是`ids=159778`，
大概每个年级都不一样。  
我现在掌握的账号有限，
学校后端也不开源，
这种开发真是相当心累了。  
关于这个键的获取方式，应该可以GET
```
http://eams.uestc.edu.cn/eams/courseTableForStd.action?_=
```
这个URL来获取，
老样子，`_`的值是unix时间戳（毫秒）。  
请求之后可以用正则的方式来从html代码中获取，
那一行一般类似于
```
bg.form.addInput(form,"ids","159778");
```
那么正则的方式就是
```
bg\.form\.addInput\(form\,\"ids\"\,\"(.*?)\"\)\;
```
注意一般有两个，取第一个，
第二个是班级课表。
一般是6位，如果不是则抛异常。

## 2018-08-23
真是想怼死张义飞。  
课程表用第一周的。
## 2018-08-22
又偷懒了几天。  
准备开始写课程表了。
对于semesterId为空的情况，
需要手动获取当前的smesterId。
不出意外的话，
可以请求
```
http://eams.uestc.edu.cn/eams/courseTableForStd.action
```
这个链接，GET方式，带一个参数`_`，
参数值为当前时间，unix时间戳，毫秒。  
然后会有Set-Cookie，
其中包含了`semester.id`，
它的值应该就是我们需要的。
我在写这些文字的时候是203。
## 2018-08-19
成绩信息里面，如果有重修的话，
会有span标签。  用正则替换搞定了。

又双叒叕出bug了。
如果有重修之类的数据，
在成绩详细信息就会有9个项目，
多了一个补考成绩，
放在第8位。
目前处理是，
去掉这个补考成绩，
主要保留的是最终成绩，
总评可有可无。  
当然对我来说，
要处理的就是把补考给去掉就行。
如果该科没有补考，那么那一项是空的。
通过正则把td标签部分解析成数组之后，
判断数组的长度，如果是9就直接扔掉第8位。

嘛，最终还是决定把补考加上。
原因当然是比较方便改。
## 2018-08-17
修复check_token.php。
居然会有idas没过期，eams过期这种奇葩的情况。
check_token.php是之前写的，
当时还没有处理eams的情况。现在把eams的确认也加到check_token里面了。

学长果然是有做产品经理的潜力。
成绩信息部分改为获取全部，
几乎得重写，靠。  
请求的URL为
```
http://eams.uestc.edu.cn/eams/teach/grade/course/person!historyCourseGrade.action?projectType=MAJOR
```
请求方式为POST，但是只提交一个  
`projectType=MAJOR`  反正我是看不懂这是什么操作。

最终还是在后端解析，用了几次正则，
把原来的那个全部注释了。
虽然有git，但是恢复起来并不是十分方便，
而且按PM的尿性，大家懂的。
## 2018-08-16
学校开放了eams，也就是教务处，
现在理论上是可以登录的，
然而小程序似乎还是不工作，
不太清楚为什么，等老哥上线吧。

同时修复了exam，grade等等的问题，login的时候塞入了明文token，已经修正。

小程序的问题似乎是没有POST数据，
不确定是不是格式的问题，
www-urlencoded或者form-data。
总之PHP是没收到数据，从日志来看。

## 2018-08-14
好多天没有写了。主要是懒，对，我被学长感染了。
学长把接口文档修改成他希望的样子，
很多地方有比较大的改动，
真是有做产品经理的潜力啊。  
域名备案终于过了，感谢祖国。

## 2018-08-06
课程表内容在js中，
首先还是得提取字符串。
关于响应的格式，
老哥让我自己去看他写的。
```
https://github.com/Yidadaa/UESTC_Helper/blob/master/src/components/course/parser.js#L95
```
实在是太秀了。

总的来说，最后决定直接用老哥的代码。
在服务器搭建node环境，
然后php调用。

目前要做的就是爬取课程表html数据了。
URL如下
```
http://eams.uestc.edu.cn/eams/courseTableForStd!courseTable.action
```
请求方式为POST，参数包括
- `ignoreHead` = 不知道，默认是1
- `setting.kind` = 学生课表:std/班级课表:class
- `startWeek` = 第几周
- `project.id` = 不知道，默认是1
- `semester.id` = 学期学年，老眼熟了
- `ids` = 貌似是学生课表(142846)/班级课表（5522）


## 2018-08-04

前几天有点颓，就没做。

课程表似乎是用了js进行加载，
这就有些麻烦了。

考试信息似乎可以直接请求到，链接是
```
http://eams.uestc.edu.cn/eams/stdExamTable!examTable.action?semester.id=xxx&examType.id=1&_=xxx
```
和成绩信息类似，不过多了一个`examType.id`，
应该是用来指定考试类型的。如下：
- 1 = 期末考试
- 2 = 期中考试
- 3 = 补考
- 4 = 缓考

另外两个参数就没什么了，和之前一样。
- `semester.id` = 学年学期
- `_` = 时间（unix时间戳，毫秒）

其中`semester.id`的默认值可以通过请求下面这个链接
```
http://eams.uestc.edu.cn/eams/stdExamTable.action
```
响应值中的Set-Cookie中应该会有。

请求返回的html中包含一个table，
里面的内容就是课程信息了。
不过与成绩信息不同的是，其中可能会有
```
[考试情况尚未发布]
```
这种值。
单独占用掉5个td标签，
属性中包含
```angular2html
colspan="5"
```
用正则应该是可以处理的，并不是太难。
我在想怎么响应到客户端，
学长还没给我回复，
不知道最近又在约哪个妹子。

## 2018-08-01
计算token那一段感觉需要再处理下，只计算一轮hash就可以了。

另外就是目前token并没有防爆破，也没设置过期时间。

关于读取成绩信息，请求的URL为
```
http://eams.uestc.edu.cn/eams/teach/grade/course/person!search.action?semesterId=xxx&projectType=&_=xxx
```
显然，有三个参数
- `semesterId`=学期（比如2017-2018第二学期=183）
- `projectType`=留空
- `_`=当前时间（unix时间戳，毫秒）
同时，提交的cookie也多了一个`semester.id`，
从数值上看应该是与`semesterId`一致的。
当然这个值是通过Set-Cookie获取的，
所以我只能从经验来判断规律了。
---
学长说他会把`semesterId`通过post提交上来，那我就不关心那玩意了。

还有一种情况就是，读取默认学期。
比如正常浏览的时候会发现，虽然有2018-2019学年第1学期，
但是系统默认并不会跳转到那个位置，
而是给出了2017-2018学年第2学期。  
从加载的情况来看，应该是这个URL的作用
```
http://eams.uestc.edu.cn/eams/teach/grade/course/person.action
```
在不提交`semester.id`这个cookie的情况下，
该URL的响应包含Set-Cookie。
当然就是`semester.id`。它的值应该就是那个默认的学年学期。

成绩查询模块开发完成。  
眼睛有点累，老王又不陪我打球，唉。
大概后期会放慢一些速度，
目前主要还剩课程表和考试信息，
搞定那两个就差不多了。
最后分析一下安全，
项目大概就可以进入稳定期。
## 2018-07-31
域名还没备案，无法接入微信。

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
最近学了一点正则，
修改了`url.php`中处理header字符串的方式。  

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

## 2018-07-27
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

- `username` = 学号
- `_` = 当前时间（unix时间戳，单位毫秒），对，就是一个下划线，你没看错

返回的内容一般有两种（不含引号）

- `true\n` = 需要验证码，可以让用户先手动登录一次再退出
- `false\n` = 不需要验证码，可以直接登录

如果不想太麻烦用户的话，可以直接获取验证码输入就行。
链接是
```
http://idas.uestc.edu.cn/authserver/captcha.html
```

这个连接是写在js里面的，
有时候（就是点换一张的时候）需要加一个参数ts，
至于值是多少我就不确定了，自己去看js代码吧。




