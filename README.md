# Pay with a Like

**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**

## Translations

Translation files can be found at https://github.com/wpmudev/translations

## Pay with a Like lets your visitors exchange a Like, +1, Tweet or share for access to articles, videos, ebooks, coupons – pretty much anything you can dream up.

 

![Offer users special rewards for sharing your content.](https://premium.wpmudev.org/wp-content/uploads/2012/05/Front-end-735x470.jpg)

 Offer users special rewards for sharing your content.

### Social Share Wall

It's like a paywall, except you get users to share your website instead of asking for cash. Create a social media buzz that increases sales, builds your client base and boosts search engine rankings. Reward users that share your site. 

### A Creative Marketing Solution

Pay with a Like even covers custom post types for creative flexible marketing. Give fans a free music download when they promote your tour schedule or embed a coupon on a [MarketPress](http://premium.wpmudev.org/project/e-commerce/) product page that reveals when the product is shared.  

![Toggle button style to best fit your site layout.](https://premium.wpmudev.org/wp-content/uploads/2012/05/pay-button-735x470.jpg)

 Toggle button style to best fit your site layout.

### Integrate with Any Site

Works beautifully with any theme and provides a guide for custom CSS to create a perfect fit. Toggle share buttons, pick the best  layout, arrange the order and set a container width that makes your like buttons pop. 

## Usage

For help with installing plugins please see our** [Plugin installation guide](https://premium.wpmudev.org/wpmu-manual/installing-regular-plugins-on-wpmu/). ** This plugin can be installed on a per site basis or **Network Activated** but currently does not offer any network wide settings (Each site within the network still controls its own settings). So now you have the plugin and you are raring to go eh! Lets crack on and get it **Activated**. When you first activate it there will be a notice in the top area of the plugins admin: 

![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-A-Like-Activate-Message.jpg)


 Its just a friendly reminder to get the plugin settings sorted first. :) Click on the **Settings** link there or go through: _Admin → Settings → Pay With A Like_

### Global Settings

This is where you get everything set up, some of the options here can be overridden on individual article pages albeit posts, pages, products, or other. 

![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-a-Like-Settings-General.jpg)

 **Activation For Posts:**

_**Disabled For All Posts**_ – This will disable _Pay With A Like_ on all posts allowing for you to select which articles are protected on the article editor screen. In other words you can set how each post will work as you create or edit it.

_**Enabled For All Posts**_ – By default all posts have _Pay With A Like_ enabled, depending on the criteria set in the following options it would mean every post will require a like.

(If _Selection Tool_ is selected for the option to _Revealed Content Selection Method_ then only content protected with our shortcode will be protected)

**Activation For Pages:**

_**Disabled For All Pages**_ – This will disable _Pay With A Like_ on all pages allowing for you to select which articles are protected on the article editor screen in other words you can set how each page will work as you create or edit it.

_**Enabled For All Pages**_ – By default all pages have _Pay With A Like_ enabled, depending on the criteria set in the following options it would mean every page will require a like.

 (If _Selection Tool _is selected for the option to _Revealed Content Selection Method _then only content protected with our shortcode will be protected)

**Activation For Custom Post Types:**

_**Disabled For All Custom Post Types**_ – This will disable Pay With A Like on all Custom Post Types allowing for you to select which articles are protected on the article editor screen in other words you can set how each Custom Post Type will work as you create or edit it.

_**Enabled For All Custom Post Types**_ – By default all Custom Post Types have Pay With A Like enabled, depending on the criteria set in the following options it would mean every Custom Post Types will require a like.

(If _Selection Tool _is selected for the option to _Revealed Content Selection Method _then only content protected with our shortcode will be protected)

**Revealed Content Selection Method:**

_This is a rather important part and its crucial you understand what is happening here otherwise you might have issues in seeing why something does or does not work._

_**Automatic Excerpt From Content**_ **–** When this option is enabled you will see a box _Except Length (Words)_ This will default to 20 words, you can set that as you please.

Keep in mind that if you have an article which is less than 20 words then it will not be protected by Pay With A Like and so it will be visible to all. (This does not count when protecting content through shortcodes)

_**Manual Excerpt From Content**_ **–** If you want greater control over what is shown then this is the better option (unless you use the Selection Tool). Rather than potentially cutting off that vital word or sentence you can use the WordPress in built Excerpt on each article.



![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-A-Like-Custom-Excerpt.jpg)

**Use Selection Tool** – With this is selected content will not be protected automatically. The Selection Tool refers to our shortcode button. All content within that shortcode will be protected.

You will be able to use a custom description which will display above the social networking buttons.

**Accessibility Settings** 

![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-a-Like-Settings-Accessibility.jpg)


**Enable On The Home Page –** When set to yes all items on the home page will also be protected based on the your global or specific article settings.

**Enable For Multiple Post Pages** – Enables the plugin for pages (except the home page) which contain content for more than one post/page, e.g. archive, category pages. Some themes use excerpts here so enabling our plugin for these pages may cause strange output.

**Admin Sees Full Content** – Sometimes you just want to test if content is protected or not. Of course being an admin you would see content by default. This setting will turn that off, so you see what everyone else will see. Default is **Yes** so admins can see all the content unless this is changed.

**Authorized Users See Content** – This will allow you to give access to users so they can see the content which is protected by our _Paying With A Like_. This one is handy when you want your registered users to have access by default but still have regular users share your site in order to get that special access.

Another potential use is when you want your staff to have access but still require your members and readers to _Pay With A Like_.

**User Level Where Authorization Starts** – This will only become available if _Authorized Users See Content_ is set to _yes_.

You will be able to select which level gets access.

**Note:** The selected level and all above will get access.

**Search Bots See Full Content** – If you are wanting search engines to index your protected content you can mark this to Yes. Keep in mind that means your content can potentially be seen publicly through search engines.

**Cookie Validity Time (Hours)** – Restrict the access time allowed once they like your article. Setting this to zero '**0**' will result in the session ending upon the browser being closed thus forcing them to like again if they want further access later when their visit your site next.

**Social Button Settings** 

![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-a-Like-Settings-Social-Button-Settings.jpg)

**Buttons To Use** – You might not want all the social networks we offer in this plugin so we built in a nifty little feature where you can disable the ones you don't like. Just uncheck them.

**Load Scripts For** – Occasionally you might find issues when running other plugins or themes with social networking abilities. This is often because they include conflicting javascript. For this reason we built in the option so you can easily disable the javascript for a specific social network.

**Description Above The Buttons** – Set your default message here to appear above all of the social networking buttons. This can be overridden when using shortcodes to protect content.

**Site Wide Like** – If you wish for all content throughout your whole site to be revealed upon Paying With A Like on one single article then set this to _Yes_.

**URL To Be Liked** – By default when articles are liked throughout your site the page they are being liked from will be used to link back to. In some instances you might like to have all backlinks being sent to a specific page on your site, well you can set that link here. Just enter the url.

**Like Random Page** – When an article is liked, you can send those backlinks to random pages through your website.

Easy peasy eh!

### In the Editor

The _Pay With A Like_ plugin will work with all Post Types. This includes Posts and Pages in addition to our MarketPress, Directory, Q&A and all other Custom Post Types. Once the settings are done that is pretty much it for blanket cover of your articles so now lets make a new post and take a look at the editor. You will notice two new additional items here, a button and a _Pay With A Like_ area. So for this example it was just a post: Admin --> Posts --> Add New 

![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-A-Like-Shortcode-Button.jpg)

 When using the selection tool aka shortcode it will generate the following for you: _[pwal id="5206505" description="Custom Description"][/pwal]_ The PWAL ID is uniquely set by the plugin. Click on the button and it will ask you for a description, this is the text which will show above all the social networking buttons. (This will mean the option you set within your settings area will not be relevant here) **Pay With A Like Meta Box** On post pages you will have a new option, if you don't see it then pull down the screen options and ensure it is selected. 

![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-A-Like-Screen-Options.jpg)


 **Enabled:**

**Follow Global Settings –** This will force the article to follow the options you setup within the settings panel earlier.

**Always Enabled** – This forces _Pay With A Like_ within the article. (Overriding the global settings)

**Always Disabled** – This will disable _Pay With A Like_ on within the article. (Overriding the global settings)

**Method:** The Method is how your content is protected, you can choose to use the global options set earlier or do something custom.

**Follow Global Settings** – Select this option if you wish to use the global options you created in the settings panel

**Automatic Excerpt** – When selected you will be given an option to enter how many words to show for the excerpt. (Overriding the global settings) This will take the first X amount of words and then display them for the article requiring a Like to see the content.



![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-A-Like-Meta-Box-Automatic-Excerpt.jpg)


**Manual Excerpt** – This will take the manual excerpt entered in the WordPress excerpt box. (Overriding the global settings)



![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-A-Like-Custom-Excerpt.jpg)


**Selection Tool** – With this option you will be able to use the shortcode button to cover specific items within the article body. (Overriding the global settings)

Here is how it looks from the admin:



![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-A-Like-Shortcode-Example.jpg)


And then on the front end:


![image](https://premium.wpmudev.org/wp-content/uploads/2012/05/Pay-With-A-Like-Protected-Shortcode1.jpg)


Notice the custom description is set there? :)

And thats all there is to it, all that awesome power under the hood with an easy to use and straight forward interface. Simples! :
