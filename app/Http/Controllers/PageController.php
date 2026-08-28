<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Show the public landing page.
     */
    public function home(): View
    {
        $providers = [
            ['name' => 'Dialog',      'logo' => 'dialog.png',     'tag' => 'Mobile · TV'],
            ['name' => 'SLT-Mobitel', 'logo' => 'sltmobitel.png', 'tag' => 'Mobile · Fibre'],
            ['name' => 'Hutch',       'logo' => 'hutch.png',      'tag' => 'Mobile · Data'],
            ['name' => 'Airtel',      'logo' => 'airtel.png',     'tag' => 'Mobile · Data'],
            ['name' => 'Lanka Bell',  'logo' => 'lankabell.png',  'tag' => 'Broadband'],
            ['name' => 'PEO TV',      'logo' => 'peotv.png',      'tag' => 'Television'],
            ['name' => 'CEB',         'logo' => 'ceb.png',        'tag' => 'Electricity'],
            ['name' => 'LECO',        'logo' => 'leco.png',       'tag' => 'Electricity'],
            ['name' => 'NWSDB',       'logo' => 'nwsdb.png',      'tag' => 'Water Bills'],
        ];

        $services = [
            [
                'title' => 'Mobile Reload',
                'icon'  => 'mobile',
                'desc'  => 'Prepaid top-ups for every Sri Lankan network, processed as soon as your slip is verified.',
                'tags'  => ['Dialog', 'Mobitel', 'Hutch', 'Airtel'],
                'badge' => 'Popular',
            ],
            [
                'title' => 'Data Packages',
                'icon'  => 'wifi',
                'desc'  => 'Daily, weekly and monthly data bundles plus add-ons and night packs.',
                'tags'  => ['Daily', 'Weekly', 'Monthly'],
                'badge' => null,
            ],
            [
                'title' => 'Postpaid Bills',
                'icon'  => 'bill',
                'desc'  => 'Settle your monthly mobile bill without queuing at a service centre.',
                'tags'  => ['All networks'],
                'badge' => null,
            ],
            [
                'title' => 'Broadband & ISP',
                'icon'  => 'router',
                'desc'  => 'Pay your home fibre, ADSL or 4G router bill and renew data quotas.',
                'tags'  => ['SLT Fibre', 'Lanka Bell', 'Dialog Home'],
                'badge' => null,
            ],
            [
                'title' => 'Electricity',
                'icon'  => 'bolt',
                'desc'  => 'Clear your CEB or LECO unit bill without queuing, with a receipt sent back to you.',
                'tags'  => ['CEB', 'LECO'],
                'badge' => null,
            ],
            [
                'title' => 'Water Bills',
                'icon'  => 'drop',
                'desc'  => 'Pay National Water Supply & Drainage Board bills any time of day.',
                'tags'  => ['NWSDB'],
                'badge' => null,
            ],
            [
                'title' => 'TV & Streaming',
                'icon'  => 'tv-card',
                'desc'  => 'Recharge Dialog TV, PEO TV and renew your favourite channel packs.',
                'tags'  => ['Dialog TV', 'PEO TV'],
                'badge' => null,
            ],
            [
                'title' => 'Gift Cards',
                'icon'  => 'gift',
                'desc'  => 'Top up game credits, streaming subscriptions and digital vouchers.',
                'tags'  => ['Games', 'Streaming'],
                'badge' => null,
            ],
        ];

        $steps = [
            ['title' => 'Choose a service',     'desc' => 'Pick mobile reload, a data pack, or the utility bill you need to settle.', 'icon' => 'grid'],
            ['title' => 'Enter the details',    'desc' => 'Type the mobile number or account number and the amount you want to pay.',  'icon' => 'form'],
            ['title' => 'Transfer & upload slip', 'desc' => 'Send the amount to our bank account, then upload your deposit slip right here.', 'icon' => 'upload'],
            ['title' => 'We verify & deliver',  'desc' => 'Our team confirms your slip and the top-up is credited, with a receipt sent to you.', 'icon' => 'check'],
        ];

        $features = [
            ['title' => 'Fast turnaround',          'desc' => 'Most orders are checked and credited within minutes of your deposit slip being uploaded.', 'icon' => 'clock'],
            ['title' => 'Minimum service charge',   'desc' => 'Just a small, clearly shown fee on every transaction. No hidden markup, no surprises at checkout.', 'icon' => 'tag'],
            ['title' => 'Every major provider',     'desc' => 'All four mobile networks plus CEB, LECO, NWSDB, SLT, Lanka Bell and TV operators.', 'icon' => 'users'],
            ['title' => 'Safe & accountable',       'desc' => 'Payments go straight to our verified bank account, and every order is receipted and traceable.', 'icon' => 'shield'],
            ['title' => 'Real people, 24/7',        'desc' => 'Sinhala, Tamil and English support on WhatsApp and phone, any hour of the day.', 'icon' => 'headset'],
        ];

        $stats = [
            ['value' => '25,000+', 'label' => 'Happy customers'],
            ['value' => '1M+',     'label' => 'Reloads delivered'],
            ['value' => '4.9/5',   'label' => 'Average rating'],
            ['value' => '24/7',    'label' => 'Always available'],
        ];

        $contact = \App\Models\Setting::contact();

        return view('pages.home', compact(
            'providers', 'services', 'steps', 'features', 'stats', 'contact'
        ));
    }

    public function support(): View
    {
        return view('pages.support', ['contact' => \App\Models\Setting::contact()]);
    }

    public function privacy(): View
    {
        return view('pages.privacy', ['contact' => \App\Models\Setting::contact()]);
    }

    public function terms(): View
    {
        return view('pages.terms', ['contact' => \App\Models\Setting::contact()]);
    }

    public function refund(): View
    {
        return view('pages.refund', ['contact' => \App\Models\Setting::contact()]);
    }
}
