# UESTC-Life API 接口文档

1. 禁止返回值为空值，响应的请求里面最少要包括`success`，`error_code`，`error_msg`三个值，允许后两者为`null`；
2. 区分响应值中的字符串和数字，不要使用字符串来传递数值型数据；
3. 如无特殊说明，所有请求返回值均为`JSON`。

### 登录页

登录可以用于验证学号和密码。

#### 登录 - `login.php` - `POST`  

@request:  
```jsonc
{
  username: String,
  passwd: String,
  cap: String // 可选，字段为空则不发送验证码
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
101|正常登录|/
102|需要验证码|验证码图片通过bae64转换后放置在cap_img
103|学号或密码错误|/
104|验证码错误|/
105|后端系统错误|/
106|请求错误|一般是错误的code
107|token错误|这种情况最好是重新登录吧
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
  token_is_availabe: Boolean, // token是否有效
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
  username: String,
  semesterId: String // 学年代号，为空时默认返回最近学年成绩信息
}
```

@return:
```jsonc
{
  success: Boolean,
  error_code: Number, // 201. token验证失败 202. 未知错误 203. 验证失败
  error_msg: String,
  data: [{
    semester: String, // 学年学期
    course_code: String, // 课程代码
    course_id: String, // 课程序号
    course_name: String, // 课程名称
    course_type: String, // 课程类别
    point: Number, // 学分
    grade: Number, // 总评成绩
    makeup_grade: Number, // 补考总评
    final_grade: Number, // 最终成绩
    gpa: Number // 绩点
  }, 
  // ...
  ]
}
```

#### 课程表 - `timetable.php` - `POST`
获取课程表。

@request:
```jsonc
{
  token: String,
  username: String,
  semesterId: String // 学年代号，为空时默认返回最近学年课程表信息
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
读取考试信息需要提交学号、token和semesterId。

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
    address: String, // 考试地点
    number: String, // 座位号
    detail: String, // 考试情况
  }, 
  // ...
  ]
}
```
