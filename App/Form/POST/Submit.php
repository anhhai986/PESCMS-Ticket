<?php
/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2015 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 * @core version 2.6
 * @version 1.0
 */
namespace App\Form\POST;

class Submit extends \Core\Controller\Controller{

    /**
     * 提交工单
     */
    public function ticket(){
        $result = \Model\Ticket::insert();
        if(!empty($result) && is_array($result)){
            $this->success(
                "工单提交成功,您的受理编号为:{$result['ticket_number']}",
                $this->url('Form-View-ticket', ['number' => $result['ticket_number']]),
                -1);
        }else{
            $this->error('提交工单出错，请尝试再次提交或者联系客服。');
        }
    }

    /**
     * 回复工单
     */
    public function reply(){
        $this->checkToken();

        $number = $this->isP('number', '请选择您要查看的工单');
        $content = $this->isP('content', '请提交回复内容');
        $ticket = \Model\Ticket::getTicketBaseInfo($number);

        if (empty($ticket) || $ticket['ticket_status'] == '4') {
            $this->error('该工单不存在或者已经关闭');
        }

        if($ticket['ticket_model_verify'] == 1){
            $verify = $this->isP('verify', '请填写验证码');
            if (md5($verify) != $this->session()->get('verify')) {
                $this->error('验证码错误');
            }
        }

        \Model\Ticket::updateReferTime($ticket['ticket_id']);
        \Model\Ticket::inTicketIdWithUpdate([
            'ticket_status' => '1',
            'ticket_read' => '0',
            'noset' => ['ticket_id' => $ticket['ticket_id']]
        ]);
        \Model\Ticket::addReply($ticket['ticket_id'], $content, 'custom');

        if($ticket['user_id'] > 0){
            $user = \Model\Content::findContent('user', $ticket['user_id'], 'user_id');

            $content = "工单《{$ticket['ticket_title']}》有新回复! 单号:{$ticket['ticket_number']},请跟进!";
            \Model\Notice::addCSNotice($user,['title' => $content, 'content' => $content]);
        }

        $this->success('回复工单成功!', $this->url('Form-View-ticket', ['number' => $ticket['ticket_number']]));

    }

}