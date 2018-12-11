# uestc-life

>谨以此程序献给成电的单身狗们。  

## 如何安装

### 环境

- Web Server(nginx / Apache / IIS / ...)
- PHP(>=7.0) with composer
- MySQL / MariaDB
- Node.js  

### 部署

1. 导入`res/wechat.sql`到你的数据库，并为该库分配一个账户，强烈反对使用`root`账户！

2. 修改`lib/config.php`，修改方法参照注释。

3. 回到原来的目录，安装依赖项，运行

   ```shell
   composer install -vvv
   ```

   如果出现系统找不到`composer`，你可以尝试

   ```shell
   php composer.phar install -vvv
   ```

4. 进入`lib/3rd_lib/tt-parser`，运行命令

   ```shell
   npm install
   ```

   然后运行

   ```shell
   node index.js
   ```

   如果你希望以后台的方式运行，对于GNU/Linux的发行版操作系统，你可以使用命令

   ```shell
   nohup node index.js &
   ```

5. 玩的开心！

### 测试

请参照接口文档`API.md`。