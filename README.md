# dota2-stats
A (really bad) angular 1 + php web app which tracks dota 2 stats for certain players

I wrote this a long time a go to learn angular 1 and php whilst at uni.
I did a lot of things very poorly, and never ended up finishing it for a number of reasons (I got my first job, my friends and I cut back on dota 2 as we got older, and other sites like dotabuff and opendota got more and more comprehensive).

The data is stored in a mysql database.
The "api" is written in plain old PHP pages.
The front end is written in angular 1.2.

The data was updated via a few php scripts run on a cron job.

I've since taken it down because I have migrated to AWS and wasn't willing to pay for a DB an unused relic.
(I took a few screenshots before I shut it down though)[./screenshots].


If for some reason you want to try and get this working.
The entire DB dump is in the root.
There is some DB connection config to place in ./api/config.php (search for `##############`)
Other than that I honestly don't know - this was built before I used Git, and before I was smart enough to write things down.

***At one point, only myself and god knew how it all worked... Now only god knows.***


Rather than removing the libraries and images that were copied in (I didn't know what npm was back then), I've left it all as is.
The libraries are licences as listed.
The images are property of valve.

