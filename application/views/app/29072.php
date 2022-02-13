<style>
    .container{
        max-width: 800px;
    }
    .text-logo{
        color:#f23e6a !important;
    }
    div {
        color: #FFFFFF;
    }
</style>

<script>
    $("#favicon").attr("href","https://s3foundation.s3-us-west-2.amazonaws.com/fed7ebd414ee52728a6d76c09293a7d0.png");
</script>

<?php


$is = $this->I_model->fetch(array(
    'i__id' => get_domain_setting(14002),
));

//echo '<h1>' .view_cover(12273,$is[0]['i__cover']) . '</h1>';

//IDEA TITLE
echo '<h1>' . $is[0]['i__title'] . '</h1>';


//MESSAGES
echo '<div class="center-frame larger-font">';
foreach($this->X_model->fetch(array(
    'x__status IN (' . join(',', $this->config->item('n___7359')) . ')' => null, //PRIVATE
    'x__type' => 4231, //IDEA NOTES Messages
    'x__right' => $is[0]['i__id'],
), array(), 0, 0, array('x__spectrum' => 'ASC')) as $count => $x) {
    echo $this->X_model->message_view( $x['x__message'], true);
}
echo '</div>';


//FEATURED IDEAS
$counter = 0;
$visible_ui = '';
$topic_id = intval(get_domain_setting(14877));
if($topic_id){
    //Go through Featured Categories:
    foreach($this->config->item('e___'.$topic_id) as $e__id => $m) {

        $query_filters = array(
            'i__type IN (' . join(',', $this->config->item('n___7355')) . ')' => null, //PRIVATE
            'x__status IN (' . join(',', $this->config->item('n___7359')) . ')' => null, //PRIVATE
            'x__type IN (' . join(',', $this->config->item('n___13550')) . ')' => null, //SOURCE IDEAS
            'x__up' => $e__id,
        );
        $query = $this->X_model->fetch($query_filters, array('x__right'), view_memory(6404,13206), 0, array('i__spectrum' => 'DESC'));
        if(!count($query)){
            continue;
        }

        $ui = '<div class="row justify-content margin-top-down-half">';
        foreach($query as $i){
            $ui .= view_i(14877, 0, null, $i);
        }
        $query2 = $this->X_model->fetch($query_filters, array('x__right'), 1, 0, array(), 'COUNT(x__id) as totals');
        $ui .= '</div>';


        $visible_ui .= view_headline($e__id, null, $m, $ui, !$counter);
        $counter++;
    }
}
echo $visible_ui;


//Info Boxes:
//echo view_info_box();


echo '<img src="/img/valentinu/Valentinu Doge B type@2x.png" style="width:110px;" class="round">';
echo '<img src="/img/valentinu/Valentinu Doge B wide@2x.png" style="width:110px;" class="round">';
echo '<img src="/img/valentinu/Valentinu Doge B@2x.png" style="width:110px;" class="round">';
echo '<img src="/img/valentinu/Valentinu Doge@2x.png" style="width:110px;" class="round">';



//SOCIAL FOOTER
$social_nav = '<div class="center-icons">
    <a href="https://t.me/mcbrokebsc"><img src="/img/mcbroke/telegram.svg" class="Footer_link__DBs2K" style="background-color:#FFF; border-radius: 50%;"></a>
    <a href="javascript:alert(\'Twitter Coming Soon...\')" target="_blank"><img src="/img/mcbroke/twitter.png"></a>
    <a href="javascript:alert(\'Coming Soon...\')"><img src="/img/mcbroke/light-bscscan.svg" class="Footer_link__DBs2K" style="background-color:#FFF; border-radius: 50%;"></a>
    <a href="javascript:alert(\'Coming Soon...\')"><img src="/img/mcbroke/light-cmc.svg" class="Footer_link__DBs2K" style="background-color:#FFF; border-radius: 50%;"></a>
    <a href="javascript:alert(\'Coin Gecko Listing Coming Soon...\')"><img src="/img/mcbroke/coingecko.svg"></a>
    <a href="javascript:alert(\'DexTools Link Coming Soon...\')"><img src="/img/mcbroke/dextools.svg"></a>
    <a href="javascript:alert(\'PooCoin Coming Soon...\')"><img src="/img/mcbroke/poocoin.svg"></a>
</div>';


$call_to_action = '<a class="btn btn-default" href="#"><i class="fas fa-file-certificate"></i> White Paper</a>
    <a class="btn btn-default" href="#"><i class="fas fa-usd-circle"></i> Buy Now</a>';

?>


<p style="padding-bottom: 21px; text-align: center">
    <a class="btn btn-default" href="#tokenomics"><i class="fas fa-coins"></i> Tokenomics</a>
    <a class="btn btn-default" href="#roadmap"><i class="fas fa-clipboard-list-check"></i> Roadmap</a>
    <a class="btn btn-default" href="#team"><i class="fas fa-users"></i> Team</a>
    <?php echo $call_to_action; ?>
</p>


<?php echo $social_nav ?>

<br />
<br />


<br />
<br />
<br />
<div style="text-align: center;">🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹</div>
<br />
<br />
<a name="tokenomics">&nbsp;</a>
<h2 class="text-center main_title">Tokenomics</h2>

<br />
<div class="row justify-content-center" style="text-align: center; color: #000;">
    <div class="col-12 col-md-4">
        <div class="info_box_cover">9%</div>
        <div class="info_box_title" style="color: #e50201 !important;">Flower Gifts</div>
        <div class="info_box_message">Towards our eCommerce platform to buy & deliver flowers in 96 countries across 4 continents</div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info_box_cover">5%</div>
        <div class="info_box_title" style="color: #e50201 !important;">Liquidity</div>
        <div class="info_box_message">To keep growing our liquidity pool & build a strong ecommerce business around gift deliveries</div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info_box_cover">1%</div>
        <div class="info_box_title" style="color: #e50201 !important;">Marketing</div>
        <div class="info_box_message">So the world hears about our community of lovers who share a passion for making their partners feel special</div>
    </div>
</div>
<br />
<br />

<p style="text-align: center; color: #000 !important; font-weight: bold; font-size: 1.1em">We serve you our 24/7 model to sustain the growth and success of the Valentine Doge ecosystem.</p>

<br />
<br />
<p style="text-align: center">
    <a class="btn btn-default" href="javascript:alert('Coming Soon...')"><i class="fas fa-file-certificate"></i> Contract</a>
    <a class="btn btn-default" href="javascript:alert('Coming Soon...')"><i class="fas fa-lock"></i> IP Lock</a>
</p>


<br />
<br />
<div style="text-align: center;">🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹🌹</div>
<br />
<a name="howtobuy">&nbsp;</a>
<h2 class="text-center main_title">How To Be $BROKE?</h2>
<br />
<p style="color: #e50201 !important; font-weight: bold; font-size: 1.5em;" >in 3 easy steps...</p>

<div style="text-align: center; position: relative; display: none;">
    <a href="https://www.youtube.com/watch?v=KpF41eS3YZQ" target="_blank" class="htb-link-video"><img src="https://www.babycake.app/images/play.svg" loading="lazy" style="transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(0deg) skew(0deg, 0deg); transform-style: preserve-3d;" alt="" class="play">
        <div style="opacity: 0;" class="video-overlay"></div>
    </a>
</div>


<div class="row justify-content-center">
    <div class="col-12 col-md-4">
        <div class="info_box_cover"><img src="/img/mcbroke/metamask-2728406-2261817.png" style="width:110px;"></div>
        <div class="info_box_title">Setup MetaMask</div>
        <div class="info_box_message" style="text-align: left;">
            <ul>
                <li>
                    Download MetaMask or TrustWallet.
                </li>
                <li>
                    Add the Binance Smart Chain to your network-list.
                </li>
            </ul>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info_box_cover"><img src="/img/mcbroke/coinbase.png" style="width:110px;" class="round"></div>
        <div class="info_box_title">Buy & Send BNB</div>
        <div class="info_box_message" style="text-align: left;">
            <ul>
                <li>
                    Buy BNB on an exchange. (i.e. Binance, Kraken, Coinbase etc.).
                </li>
                <li>
                    Transfer the tokens to your MetaMask wallet address. BEP-20 addresses start with a "0x".
                </li>
            </ul>

        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info_box_cover"><img src="/img/mcbroke/pancakeswap-cake-logo.png" style="width:110px;"></div>
        <div class="info_box_title">Swap on Pancake</div>
        <div class="info_box_message" style="text-align: left;">

            <ul>
                <li>
                    You can <a class="btn btn-default" href="#howtobuy">BUY NOW</a> on PancakeSwap.
                </li>
                <li>
                    Select $BROKE or copy/paste contract address.
                </li>
                <li>
                    Set slippage tolerance to 12-18%
                </li>

            </ul>

        </div>
    </div>
</div>


<br />
<br />
<br />
<br />
<a name="roadmap">&nbsp;</a>



<div style="text-align: center; color: #000; background-color: #FFCC00; padding: 21px 0 55px;">

    <h2 class="text-center main_title">Our $BROKE Roadmap</h2>
    <div style="text-align: center;"><img src="/img/mcbroke/mirror.jpg" class="rounded-corner"></div>

    <div class="row justify-content-center">
        <div class="col-12 col-md-4">
            <div class="info_box_cover"><i class="fad fa-coffee-pot"></i></div>
            <div class="info_box_title">Phase 1</div>
            <div class="info_box_message" style="text-align: left; margin-left: 13px">
                <i class="fas fa-check-circle"></i> Website V1
                <br /><i class="fas fa-check-circle"></i> Lock & Burn Tokens
                <br /><i class="fal fa-circle"></i> List on CMC & CG
                <br /><i class="fal fa-circle"></i> Contract Audits
                <br /><i class="fal fa-circle"></i> Rewards Dashboard
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="info_box_cover"><i class="fad fa-french-fries"></i></div>
            <div class="info_box_title">Phase 2</div>
            <div class="info_box_message" style="text-align: left; margin-left: 13px">
                <i class="fal fa-circle"></i> Website V2
                <br /><i class="fal fa-circle"></i> Social Campaigns
                <br /><i class="fal fa-circle"></i> Promotional Contests
                <br /><i class="fal fa-circle"></i> The $BROKE House Project
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="info_box_cover"><i class="fad fa-burger-soda"></i></div>
            <div class="info_box_title">Phase 3</div>
            <div class="info_box_message" style="text-align: left; margin-left: 13px">
                <i class="fal fa-circle"></i> The McBroke DAO
                <br /><i class="fal fa-circle"></i> The McBroke NFT
                <br /><i class="fal fa-circle"></i> More Partnerships
                <br /><i class="fal fa-circle"></i> Livestream Giveaways</div>
        </div>
    </div>
</div>


<br />
<br />
<br />
<br />



<a name="team">&nbsp;</a>
<h2 class="text-center main_title">Our $BROKE Team</h2>

<div class="row justify-content-center" style="text-align: center; color: #000; ">

    <div class="col-12 col-md-4">
        <div class="info_box_cover"><img src="https://s3foundation.s3-us-west-2.amazonaws.com/29739cef8658db08fbbff40530eb1814.jpeg" style="height:150px;"></div>
        <div class="info_box_title">McFlurry</div>
        <div class="info_box_message">CTO & Developer</div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info_box_cover"><img src="/img/mcbroke/chicken.jpg" style="height:150px;"></div>
        <div class="info_box_title">McNuggets</div>
        <div class="info_box_message">CMO & Product</div>
    </div>
    <div class="col-12 col-md-4">
        <div class="info_box_cover"><img src="/img/mcbroke/rib.jpg" style="height:150px;"></div>
        <div class="info_box_title">McRib</div>
        <div class="info_box_message">CCO & Community</div>
    </div>
</div>


<br />
<br />
<br />
<br />
<img src="/img/mcbroke/McBroke-trucker-hat@2x.jpg" style="max-width: 300px; margin: 34px 0; ">
<br />
<br />
<br />
<br />


<p style="padding-bottom: 21px; text-align: center">
    <?php echo $call_to_action; ?>
</p>


<?php echo $social_nav ?>
