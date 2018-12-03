/*
let express = require('express')
let app = express()
let counts = 0

app.post('/', function (req, res) {
  res.send('Hello World!')
  counts += 1
  //console.log(req.ip)
  console.log(req.query)
  console.log(req.body)
})

app.listen(30001, function () {
  console.log('Example app listening on port 30001!')
})*/
const func = require('./parser.js')

var app = require('express')();
var bodyParser = require('body-parser');
var multer = require('multer'); // v1.0.5
var upload = multer(); // for parsing multipart/form-data

app.use(bodyParser.json()); // for parsing application/json
app.use(bodyParser.urlencoded({ extended: true })); // for parsing application/x-www-form-urlencoded

app.post('/', upload.array(), function (req, res, next) {
  var mes = func.parseCourseData(req.body.content)
  //console.log(mes);
  //res.json(req.body);
  res.send(mes)
});

app.listen(30001, function () {
  console.log('Example app listening on port 30001!')
})