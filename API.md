# UESTC-Life API 接口文档

1. 禁止返回值为空值，响应的请求里面最少要包括`success`，`error_code`，`error_msg`三个值，允许后两者为`null`；
2. 区分响应值中的字符串和数字，不要使用字符串来传递数值型数据；
3. 如无特殊说明，所有请求返回值均为`JSON`。
### 统一错误码
除特别指定的外，以此表为准。

值|说明|备注
:---:|:---:|:---:
201|toekn错误|建议重新登录
202|后端未知错误|大多数情况是数据库挂了
203|无法连接教务处|等校方处理
204|学号或密码错误|

### 登录页

登录可以用于验证学号和密码。

#### 登录 - `login.php` - `POST`  

@request:  
```jsonc
{
  username: String,
  passwd: String,
  cap: String, // 可选，字段为空则不发送验证码
  token: String //如果有cap，则为必须项
}
```

@return:  
```jsonc
{
  success: Boolean,
  error_code: Number, // 在这里列举错误码
  error_msg: String, // 用于返回错误详情
  cap_img: String, // 可选，base64编码后的验证码图片，如果直接登录成功，该字段为空
  token: String // 如果登录成功，则返回token，如果需要验证码，该字段为空
}
```

状态码|含义|备注
:---:|:---:|:---:
102|需要验证码|验证码图片通过bae64转换后放置在cap_img
103|学号或密码错误|/
104|验证码错误|/
105|后端系统错误|一般是数据库挂了或者学校网站升级
106|请求错误|一般是没有username或者passwd
107|token错误|这种情况最好是重新登录吧
108|教务处登录失败|一般是学校教务处网站关闭或升级
#### 检测token有效性 - `check_token.php` - `POST`
用于确认token所属的cookie是否有效

@request:
```jsonc
{
  username: String, // 学号
  token: String // 用于校验的token
}
```

@return:
```jsonc
{
  token_is_available: Boolean, // token是否有效
  success: Boolean,
  error_code: Number, 201. 验证失败 202. 未知错误
  error_msg: String
}
```
#### 获取成绩信息 - `grade.php` - `POST`
读取成绩信息需要提交学号、token和semesterId。

@request:
```jsonc
{
  token: String,
  username: String // 不需要提供semesterId数据，直接返回所有学期的成绩即可
}
```

@return:
```jsonc
{
  success: Boolean,
  error_code: Number, // 201. token验证失败 202. 未知错误
  error_msg: String,
  data: {
    summary: {
      aver_gpa: Number, // 平均gpa
      sum_point: Number, // 总学分
      course_count: Number, // 总课程数
      time: String // 统计时间
    },
    semester_summary:[{
      semester_year: String, // 如 2016-2017
      semester_term: Number, // 1或者2，表示上下学期
      course_count: Number, // 该学期总门数
      sum_point: Number, // 该学期总学分
      aver_gpa: Number // 该学期平均绩点
    },
    // ...
    ],
    detail: [{
      semester: String, // 学年学期
      course_code: String, // 课程代码
      course_id: String, // 课程序号
      course_name: String, // 课程名称
      course_type: String, // 课程类别
      point: Number, // 学分
      grade: Number, // 总评成绩
      re_grade: Number, //补考成绩，这一项可能没有，没有补考的话值是null
      final_grade: Number, // 最终成绩
      // makeup_grade: Number, // 补考总评，所有成绩页面没有补考成绩，没有绩点
      // gpa: Number // 绩点，绩点由前端计算生成
    }, 
    // ...
    ]
  }
}
```

#### 课程表 - `timetable.php` - `POST`
获取课程表。

@request:
```jsonc
{
  token: String,
  username: String,
  semesterId: String // 学年代号，为空时默认返回最近学年成绩信息
}
```

@return:
```jsonc
{
  success: Boolean,
  error_code: Number,
  error_msg: String,
  data: [{
    course_id: String, // 课程序号
    course_name: String, // 课程名称
    date: String, // 排课周时间，比如"1到19周单周"、"1到5周双周"等
    room: String, // 上课地点，比如"品A105"
    time: Array, // 上课时间，比如：[[0, 1], [0, 2]]就表示周一的上午一二节课，这个数据可以直接从js中解析
    teacher: String, // 授课老师
  }, 
  // ...
  ]
}
```

#### 考试信息 - `exam.php` - `POST`
读取考试信息需要提交学号、token、semesterId、examTypeId。

@request:
```jsonc
{
  token: String,
  username: String,
  semesterId: String, // 学年代号，为空时默认返回最近学年成绩信息
  examTypeId: Number // 考试类型，1. 期末考试 2. 期中考试 3. 补考 4. 缓考
}
```
@return:
```jsonc
{
  success: Boolean,
  error_code: Number,
  error_msg: String,
  data: [{
    status: Boolean, // true. 正常 false. 未发布
    course_id: String, // 课程序号
    course_name: String, // 课程名称
    date: String, // 考试日期
    plan: String, // 考试安排
    address: String, // 考试地点
    number: String, // 座位号
    detail: String, // 考试情况
    other: String // 其他情况
  }, 
  // ...
  ]
}
```
#### 个人信息 - `person.php` - `POST`
读取个人信息需要提交学号和token。

@request:
```jsonc
{
  token: String,
  username: String
}
```
@return:
```jsonc
{
  success: Boolean,
  error_code: Number,
  error_msg: String,
  data: [{
    name: String, //姓名
    ename: String, //姓名（英文）
    major: String, //专业
    college: String, //学院
    status: String, //学籍
    type: String, //类型
    campus: String //校区
  }, 
  // ...
  ]
}
```
#### 一卡通信息 - `ecard/info.php` - `POST`
需要提供学号和token。

@request:
```jsonc
{
  token: String,
  username: String
}
```
@return:
```jsonc
{
  success: Boolean,
  error_code: Number,
  error_msg: String,
  data:{
    number: String, //卡号
    balance: Number, //余额
    status: String, //状态，一般为正常
    date: String, //卡片有效期
    uncashed_balance: Number //待领取余额
  }
}
```
#### 消费趋势 - `ecard/stat.php` - `POST`
需要提供学号和token。

@request:
```jsonc
{
  token: String,
  username: String
}
```
@return:
```jsonc
{
  success: Boolean,
  error_code: Number,
  error_msg: String,
  data:[
    ["20180831",11061225,1.1],
    ["20180901",11061225,28.5],
    ...
  ]
}
```
#### 消费地点 - `ecard/place.php` - `POST`
需要提供学号和token。

@request:
```jsonc
{
  token: String,
  username: String
}
```
@return:
```jsonc
{
  success: Boolean,
  error_code: Number,
  error_msg: String,
  data:[
    [4,"二食堂紫荆餐厅",18.9],
    [10,"二食堂清真餐厅",36.5],
    ...
  ]
}
```
#### 充值趋势 - `ecard/charge.php` - `POST`
需要提供学号和token。

@request:
```jsonc
{
  token: String,
  username: String
}
```
@return:
```jsonc
{
  success: Boolean,
  error_code: Number,
  error_msg: String,
  data:[
    [3232,"一食堂1F多媒体",100.0],
    [3229,"一食堂1F多媒体（黄色）",200.0],
    ...
  ]
}
```
#### 交易流水 - `ecard/history.php` - `POST`
需要提供学号、token、页数。  
默认爬取180天的。

@request:
```jsonc
{
  token: String,
  username: String,
  page: Number, //页数
  date_range: Number, //查询时间,7|30|60|180
  type: Number //交易类型,2=消费|1=充值|3=易支付电控
}
```
@return:
```jsonc
{
  success: Boolean,
  error_code: Number,
  error_msg: String,
  data:{
    pages: Number, //总页数
    date_range: Number, //查询时间
    payment: Number, //总消费
    charge: Number, //总充值
    detail: [
    {
      date: Number, //交易日期（ymd，不是unix时间戳）
      time: Number, //交易时间（hms，不是unix时间戳）
      device: String, //交易设备
      price: Number, //交易金额
      balance: Number, //卡内余额
    },
    ...
    ]
  }
}
```