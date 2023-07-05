
# Comments
## How to run it?
* Run Docker container and open:
* [http://127.0.0.1:3001](http://127.0.0.1:3001)
* login as user: password


## The problem to be solved
The task is to build the crawler capable to grab all links from a website and list them.

## A technical spec of how you will solve it
* I decided to build a standalone PHP app, as simple as it can be, according to expectations.
* It's made with PHP OOP with PSR + procedural elements.
* It stores data in files, instead of database. The "fake database" is loaded as arrays from a file.
* There is a simple login mechanism for admin. After login user is redirected to admin panel. I made this to show you that I understand how the login mechanism works, but I will not attach much importance to it, as well as to the "database" mechanism.
* As for the database, I would normally use a special class to communicate with the database (I like this one: https://github.com/ThingEngineer/PHP-MySQLi-Database-Class) or use build-in mechanizm within a framework. In this app I decided to use files, that the app includes when it is run in subsequent iterations.
* I use the same way (PHP arrays saved in files) to save data in one of my apps, that I created coulple of years ago. That app is processing a large amount of logging data every day downloaded from the office access control system. The processing is made one time a day, and then shown to HR department on demand. It works fine, not generating any errors during last years. That's why I used this mechanism to store data instead of MySQL.

* I tested it on PHP 7.2, 7.3, and 7.4 which is running inside a Docker container. Docker PHP 7.4 configuration files are included in the repo, so you can test it in the same environment if you want.




## The technical decisions you made and why
## How the code itself works and why, How your solution achieves the admin’s desired outcome per the user story
* The app uses php files, includes and redirections instead of some kind of a router mechanism. It is simple and it was done that way on purpose.
* There is one main class. It takes the start url and depth as inputs.
* In my opinion it makes no sense and takes almost the same effort to make a crawler that runs only one page, so I made it recursive. By default it only runs on depth=1, so it is doing the job the way you wanted ("Only crawl the home webpage, i.e. instead of recursively crawling through all of the internal hyperlinks").
* It is not crawling its own home page address, because obviously it makes no sense in this case, if we are running a test app. So I made it run some example adresses (see it in the code in file mk_sitemap.php).
* The file mk_sitemap.php is not protected by login and it is not an error. I made it this way, so that it can be launched by cron without need to login. The risk of leaving this script unprotected should not be great if we are building a small custom app with custom script url, that nobody knows. However this start script should be protected in production environment. We could for example:
    * make it accessible only from trusted IPs
    * use http authentication for script file which is launched by cron
    * login with curl and then run the script
# How it works?
* The crawler starts by checking it we need to do anything ("Only delete the temporary stored results based on time. Normally, we would also delete them when a change in the content happens. But let’s keep it really simple and only delete based on time."). 
    * It's based on time, so it's comparing last crawl time with actual time.
    * If it would be based on contens change then the crawl should be done everytime to calculate the change of the sitemap (for example with md5 or other hash function). In my opinion the crawl (especially for a big website) takes much more ressources and time then the sitemap saving part, so in this particular case the conditional sitemap record based on content change calculation is pointless, because it is not going to save any ressources.
    * If there is need to make new sitemap (>3600s until last crawl), then it's resetting temporary results going on.
    * If not, it returns and exits the app. 
* Then it runs recursive crawl function starting from first Url:
    * It marks url as checked
    * It tryes to grab the Url with curl function
        * on success it returns the page
        * on failure it marks Url as error and saves error number
        * The important thing is that this function tryes to access every Url, even if it is on the last depth level of the crawl. Theoretically it doesn't need to do it, because we can add the adress to the sitemap without accessing it. However, we do not know whether the address exists and is accessible. Since the application is to serve SEO purposes, the information about the Url is important and we need to check it.
        * the function curlErrCode() only holds Curl error codes, so it should not really be a part of the class. It's just a helper function.
    * It takes the output (the page or whatever it gets) and tryes to grab nodes from the page:
        * it uses PHP DOMDocument class
        * ii uses libxml_use_internal_errors(true); and libxml_clear_errors(); to avoid DOMDocuments warnings on invalid tags, according to this: https://stackoverflow.com/questions/7082401/avoid-domdocument-xml-warnings-in-php
    * it checks if it's the deepest crawl level:
        * yes = return
        * no = go on
    * it checks the nodes for links:
        * it passes whatever it finds to the function generating new crawl Url
        * this function parses the found Url and filters it. The purpose is to get rid of everything exept http, https, start domain links and relative links. Relative are transformed to absolute.
        * then it checks if the new Url is not already checked or marked as error
        * if it's new, then it runs the same function recursively (crawlPage()) with the new Url
    * at the end the first instance of the function saves the last crawl time
        * It saves results as indexes of an array, but it makes no difference here. Saving it as indexes helps to check duplicates and to unset if needed.
* after crawling all Urls:
    * the results are saved to "database" as arrays (first "Delete the sitemap.html file if it exists")
    * the sitemap is saved as html with information about errors
    * the home page is saved as html
* end of sitemapper job
## Run the crawl every hour ⏰🤖
* My solution is to use cron on the server or built-in cron-like solution (in WordPress) to trigger the crawl file
    * something like: */60 * * * * wget -O /dev/null http://www.my_app_domain/mk_sitemap.php
    * OR */60 * * * * php /var/www/html/mk_sitemap.php
## App results
* The results are stored in html file ("For storage, you can use a database or the filesystem.")
* The sitemap is included on admin page ("Display the results on the admin page")
* the sitemap can be accessed/included on visitors page ("On the front-end, allow a visitor to view the sitemap.html page")

## PHPCS
* the code passes PHPCS test with wp-rocket configuration phpcs.xml file (excluded Wordpress rules)