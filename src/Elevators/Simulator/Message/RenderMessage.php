<?php


namespace Elevators\Simulator\Message;


class RenderMessage
{
    /** @var string */
    protected $renderText;

    /** @var  */
    protected $type;

    /**
     * @return string
     */
    public function getRenderText(): string
    {
        return $this->renderText;
    }

    /**
     * @param string $renderText
     */
    public function setRenderText(string $renderText): void
    {
        $this->renderText = $renderText;
    }

    public function asArray()
    {
        return [
            'type' => 'render',
            'value' => $this->getRenderText()
        ];
    }

    public function asJson()
    {
        return json_encode($this->asArray());
    }
}
