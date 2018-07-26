# git使用
# 基本命令

# 克隆一个项目
git clone [url] [name]

# 初始化
git init

# 添加到暂存区
git add .

# 查看提交状态
git status

# 查看提交历史
git log

# 撤销操作
git commit –amend

# 新建分支
git branch [branch’s name]

# 分支切换
git checkout [分支名]

# 新建并切换分支
git checkout –b [分支名]

# 合并分支
git merge [分支名]

# 查看有哪些分支
git branch

# 查看哪些修改
git diff

# 回退
git reset –hard [commit_id]

# 查看命令历史，以便确定要回到未来的哪个版本。
git reflog

每次修改，如果不用git add到暂存区，那就不会加入到commit中。

当文件修改还未添加到暂存区时，撤销修改到和版本库一模一样的状态
git checkout -- <file>
当文件已经添加到暂存区add后,还未commit，又作了修改，现在，撤销修改就回到添加到暂存区后的状态。
git reset HEAD <file>
git checkout -- <file>
已经提交了不合适的修改到版本库时，想要撤销本次提交，参考版本回退一节，不过前提是没有推送到远程库。
git reset –hard [commit_id]


# 在版本库中删除文件
git rm <file>

修复bug时，我们会通过创建新的bug分支进行修复，然后合并，最后删除；当手头工作没有完成时，先把工作现场git stash一下，然后去修复bug，修复后，再git stash pop，回到工作现场。
Git友情提醒，分支还没有被合并，如果删除，将丢失掉修改，如果要强行删除，需要使用大写的-D参数。
如果要丢弃一个没有被合并过的分支，可以通过git branch -D <name>强行删除。


# 关联一个远程库
要关联一个远程库，使用命令git remote add origin git@server-name:path/repo-name.git；
关联后，使用命令git push -u origin master第一次推送master分支的所有内容；
此后，每次本地提交后，只要有必要，就可以使用命令git push origin master推送最新修改；

# 推送分支
如果要推送其他分支，比如dev，就改成：

    git push origin dev

# 建立本地分支和远程分支的关联，使用
    git branch --set-upstream branch-name origin/branch-name

# 标签
    命令git tag <tagname>用于新建一个标签，默认为HEAD，也可以指定一个commit id；
    命令git tag -a <tagname> -m "blablabla..."可以指定标签信息；   
    命令git tag可以查看所有标签。    
    命令git push origin <tagname>可以推送一个本地标签；    
    命令git push origin --tags可以推送全部未推送过的本地标签；    
    命令git tag -d <tagname>可以删除一个本地标签；    
    命令git push origin :refs/tags/<tagname>可以删除一个远程标签。



# 框架文档说明
# 一、目录结构
    + public
    |- app.php //入口文件
    |- .htaccess //重写规则    
    |+ uploads
    + config
    |- aliyun.ini    
    |- oss.ini
    |- test-db.ini //数据库配置文件
    |- test.ini ////配置文件
    + app
    |+ controllers
        |- Index.php //默认控制器
    |+ views    
        |+ index   //控制器
            |- index.html //默认视图
    |+ modules //其他模块
    |+ library //本地类库
    |+ models  //model目录


# 二、重写规则
Apache的Rewrite (httpd.conf)

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule .* index.php

    
Nginx的Rewrite (nginx.conf)

    server {
        listen ****;
        server_name  [server_name];
        root   document_root;
        index  index.php index.html index.htm;

        if (!-e $request_filename) {
            rewrite ^/(.*)  /index.php/$1 last;
        }
    }


# 三 、入口文件
    //定义项目路径
    define('APP_PATH', realpath( dirname(__FILE__).'/../') );    
    //添加配置
    $App = new \Yaf_Application(APP_PATH.'/config/'.APP_INI.'.ini', 'develop');    
    //启动项目
    $App->bootstrap()->run();


# 四、配置文件
    [common]

    ; 指定入口运行文件Bootstrap   
    application.bootstrap = APP_PATH "/Bootstrap.php"    
    ; 指定公共类库    
    application.library = APP_PATH "/library"
    ; 指定项目路径    
    application.directory = APP_PATH "/app"   
    ; 项目拥有的模块    
    application.modules =Index,Cases    
    ; 设置静态html文件后缀    
    application.view.ext = html

    [product : common]


# 五、Bootstrap提供了一个全局配置的入口
1.运行于Yaf_Application:run()之前，必须手动调用，如$app->bootstrap()->run();

2.类名必须为Bootstrap，继承自Yaf_Bootstrap_Abstract

3.类文件默认为APP_PATH下，文件名为Bootstrap.php。可以在配置文件中通过配置 application.bootstrap 指定文件位置和名称

    （application.bootstrap=APP_PATH "/application/bootstrap/Bootstrap.php"）

4.Bootstrap类中，以_init开头的方法才会被Yaf调用，接受Yaf_Dispatcher实例作为参数

    （其中_initRoute()方法中的代码是配置路由协议的代码）

# 六、控制器
在Yaf中, 默认的模块/控制器/动作, 都是以Index命名的, 可通过配置文件修改的.
对于默认模块, 控制器的目录是在app目录下的controllers目录下, Action的命名规则是"名字+Action"
init()方法是控制器被实例化时自动调用的方法，而不是__construct()

例：控制器在app目录下的命名为Test.php，文件中命名为TestController，其中的一个方法为testAction（）

方法：
# 1.forward
    通过forward可以跳转到本控制器那某函数,也可跳转到其他控制器某函数
    $this->forward('index', ['id' => 3, 'name' => 'jack']); 转发到index动作，并传了两个参数过去。
    
    1.$this->forward(‘xxx’);
    结果：(当前models即controller内)/(当前Controller)/xxxAction
    
    2.$this->forward(‘xxx’,’Yyy’);
    结果：(当前models即controller内)/YyyController/xxxAction
    
    3.$this->forward(‘xxx’,’Yyy’,’zzz’);
    结果：zzz/Yyy/xxxAction
    
    4.this−>forward(′xxx′,′Yyy′,′zzz′,params);多层
    $params = array( 
    ‘a’ => ‘1’, 
    ‘b’ => ‘2’ 
    ); 
    结果：/zzz/Yyy/xxx/a/1/b/2
# 2.redirect
    redirect 可以转向到本网站或外网
    1.$this->redirect(‘/xxx’);
    结果：/xxx   
    2.$this->redirect(‘/xxx/yyy’);
    结果：/xxx/yyy    
    3.$this->redirect(‘/xxx/yyy/zzz’);
    结果：/xxx/yyy/zzz    
    4.$this->redirect(‘http://www.baidu.com‘);
    结果：跳转至百度外网

    重定向到一个应用外部的url（用全路径）： 
        $this->redirect("http://www.baidu.com");
    重定向到另外一个控制器或模块（用绝对路径）： 
        $this->redirect("/app/goods/index"); 
        $this->redirect("/user/index");
    重定向到同一个控制器的动作（相对路径）： 
        $this->redirect("index");
# 3.render
    render直接引入视图文件  
    
    1.不指定render
    结果：(当前models即controller内)/(当前Controller)/(当前Action).phtml   
    2.$this->render(‘xxx’);
    结果：(当前models即controller内)/(当前Controller)/xxx.phtml      

# 七、视图
views下的index目录是控制器名的小写形式，模板名称则与action的小写名称对应。

# 八、模型
例：模型在app目录下命名为Test.php，文件中命名为TestModel


# 九、多模块
把模块放在 app/modules目录下，模块目录下放置该模块的控制器和视图。同时在conf/app.ini中添加该模块的名字，模块直接以逗号隔开，记得一定添加个index的模块，防止路径只有两段时出错，此时index模块对应的控制器是app/controllers目录下的

多模块目录结构

    + modules
    |+ app
        |+ controllers
            |- Hello.php    //action为hello
        |+ views  
            |+ hello    //与controller的名字一致
                |-hello.html    //与controller中的动作一致  


# 十、路由
# 1、前言
    Yaf的路由组件包括Yaf_Router和Yaf_Route_Abstract
    路由协议指导框架如何将request_uri解析到module、controller、action，以及如何解析用户提交的参数
    一个应用可以注册多个路由协议，最后注册的路由协议最先尝试（优先级最高）
    路由解析出来后会被传递给Yaf_Request_Abstract 实例
    默认的路由协议是Yaf_Route_Static，在request_uri中以 “/” 分割module、controller、action和参数的键和值
# 2、添加路由
添加路由有两种方式：通过php程序构造路由协议对象添加，通过配置文件添加。

首先得获取路由， 
方法1，通过Yaf_Application对象：

    $router = Yaf_Application::app()->getDispacher()->getRouter();

方法2，直接通过Yaf_Dispatcher对象：

    $router = Yaf_Dispatcher::getInstance()->getRouter();
本质上都是需要通过Yaf_Dispatcher 获取。然后在执行Yaf_Application的run()方法前添加路由                

# 3、路由协议介绍
# 3.1、 Yaf_Route_Static
    这是Yaf的默认路由协议，在request_uri中以 “/” 分割module、controller、action和参数的键和值。如：
    /module/controller/action/param1/value1/param2/value2
    或
    /controller/action/param1/value1/param2/value2
    
    分割处来的第一段，有可能是module，也有可能是controller，如果该module存在，则认为是module，否则认为是controller。


# 3.2、 Yaf_Route_Simple
Yaf_Route_Simple路由协议从query_string中解析出module、controller和action。 
形式如下：

    ?m=module&c=controller&a=action&param1=value1&param2=value2

创建Yaf_Route_Simple时，需要指定query_string中表示module、controller和action的参数名。 
将如下代码加在入口文件index.php的$app->run();之前

    $router = Yaf_Application::app()->getDispatcher()->getRouter();
    $simpleRoute = new Yaf_Route_Simple('a', 'b', 'c');
    $router->addRoute('simple_route', $simpleRoute);

以上代码表示，query_string中，a参数的值就是module，b参数的值就是controller，c参数的值就是action。如果有的参数没有值，则取默认值。


# 3.3、 Yaf_Route_Supervar
Yaf_Route_Supervar 是从一个query_string参数变量中解析module、controller、action和params参数，解析规则同Yaf_Route_Static。 
形如：

    ?r=/module/controller/action/param1/value1/param2/value2

    $router = Yaf_Application::app()->getDispatcher()->getRouter();
    $supervarRoute = new Yaf_Route_Supervar('r');
    $router->addRoute('supervar_route', $supervarRoute);

该代码表示表示从”r” 参数中解析module、controller、action和params参数([param1=>value1,param2=>value2])。


# 3.4、 Yaf_Route_Regex
该路由协议是通过正则表达式匹配request_uri，创建Yaf_Route_Regex路由协议时，每个正则表达式都必须指定一个module、controller、action组合。

$regexRoute = new Yaf_Route_Regex(

    '#product/([0-9]+)/([0-9]+)#', // 必须要用定界符（本例子为"#")，否则报错。

    array(
        'module' => 'app',
        'controller' => 'goods',
        'action' => 'detail',
    ),
    array(
        1 => 'cid',
        2 => 'id'
    )
);

该路由匹配如下形式的request_uri：product/1, product/2, product/12。 
匹配到之后，定位的模块为app，控制器为goods，动作为detail。array(1=>'id')表示([0-9]+)这个正则表达式匹配到的值作为参数id的值传给detail方法。


# 3.5、 Yaf_Route_Rewrite
该路由协议是通过某种模式来匹配request_uri，与Yaf_Route_Regex类似，创建Yaf_Route_Rewrite路由协议时，每个模式都必须指定一个module、controller、action组合。 
该路由协议可看成是弱正则的路由协议。

    
    $rewriteRoute = new Yaf_Route_Rewrite(
        'user/:name',
        array(
            'controller' => 'user',
            'action' => 'index'
        )
    );
    $router->addRoute('rewrite_route', $rewriteRoute);

该路由协议将形如/user/a, /user/bc, /user/1 之类的request_uri定位到user控制器的index方法。

#注意：

    冒号(:)指定了一个段,这个段包含一个变量用于传递到我们动作控制器中的变量

    （例：http://a.com/product/bar 将会创建一个变量名为ident并且其值是'bar'的变量,我们然后就可以在我们的动作控制器下获取到它的值：$this->getRequest()->getParam('ident');）

    星号(*)被用做一个通配符, 在Url中它后面的所有段都将作为一个通配数据被存储
    （例：http://a.com/product/bar/test/value1/another/value2,在'bar'后面的段都将被做成变量名/值对 
        ident = bar
        test = value1
        another = value2）


# 十一、数据操作
例如：

    <?php
    use \Cases\TestModel;
    class TestController extends Yaf_Controller_Abstract
    {
    
        public function init()
        {
            error_reporting(0);
        }
    
        public function testAction(){
             echo 233;
             return $this->redirect('hello');
        }
    
        public function helloAction(){
            $this->getView()->assign('hello',"hello qt");
        }
    
        public function indexAction()
        {
    
        }
    
        public function createAction()
        {
            //展示页面
            return $this->getView('hello');
        }
    
        //增加数据
        public function storeAction()
        {
            $data=[
                'title' => 'hello',
                'body' => 'world',
            ];
            $res = TestModel::create($data);
            if ($res){
                echo "存储数据成功";
            }else{
                echo "存储数据失败";
            }
            return false;
    
        }
    
        //查找数据
        public function showAction()
        {
            //方法一 通过where()->asArray()->find()
            //asArray()将对象转换为数组
            //find只返回满足查询条件的第一组数据select获取所有满足查询条件的记录。
            $test_one = TestModel::where('title','hello')->asArray()->find();
            //select获取所有满足查询条件的记录。
            $test_one_one = TestModel::where('title','hello')->asArray()->select();
            var_dump($test_one);
            echo "<br>";
            var_dump($test_one_one);
            echo "<hr>";
    
            //方法二 通过get()方法
            //默认通过id号得到一条数据
            $test_two = TestModel::get(1);
            //通过其他的字段号获得一条数据
            $test_two_one = TestModel::get(['title' => "one"]);
            var_dump($test_two);
            echo "<br>";
            var_dump($test_two_one);
            echo "<hr>";
    
            //方法三 查找一条数据，findOne()如果没有参数，默认查找第一条
            //默认通过id得到一条数据
            $test_three = TestModel::findOne(1);
            //通过其他字段号获得一条数据
            $test_three_one = TestModel::findOne(['title' => "hello"]);
            var_dump($test_three);
            echo "<br>";
            var_dump($test_three_one);
            echo "<hr>";
    
            //方法四 查找多条数据（self::field($fields)->where($where)->cache($cache)->asArray()->select();）
            //select获取所有满足查询条件的记录。
    
            //无参数获得所有的数据
            $test_four = TestModel::findAll();
            //获得满足条件的数据
            $test_four_one = TestModel::findAll(['title' => "hello"]);
            var_dump($test_four);
            echo  "<br>";
            var_dump($test_four_one);
            echo  "<hr>";
    
            //方法五 查找所有数据
            $test_five = TestModel::all();
            var_dump($test_five);
            echo "<hr>";
    
    //        $test_six = TestModel::getSelect(['title' => "one"]);
    //        var_dump($test_six);
            return false;
        }
    
        public function editAction()
        {
    
        }
    
        //修改数据
        public function updateAction()
        {
            $data = [
                'title' => "title",
            ];
            $test = TestModel::update($data,['id' => 1]);
            var_dump($test);
        }
    
        //删除数据
        public function destroyAction()
        {
            //删除一条数据
            $test = TestModel::get(20);
            $res = $test->delete();
            if ($res){
                echo "删除数据成功";
            }else{
                echo "删除数据失败";
            }
    
            //删除所有数据
            $res1 = TestModel::destroy();
            if ($res1){
                echo "删除所有数据成功";
            }else{
                echo "删除所有数据失败";
            }
            return false;
        }
    }
    ?>
