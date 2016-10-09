@include('ui::form.text', [
  'label' => '页面标题',
  'name' => 'TKD[title]',
  'extra' => [],
  'placeholder' => '页面标题',
  'helper' => '若留空则表示采用网站自己的规则生成',
  'data' => [],
  'default' => isset($data['TKD']) && isset($data['TKD']['title']) ? $data['TKD']['title'] : ''
])
@include('ui::form.tags', [
  'label' => '页面关键字',
  'name' => 'TKD[keywords]',
  'extra' => [],
  'placeholder' => '页面关键字',
  'helper' => '若留空则表示采用网站自己的规则生成',
  'data' => [],
  'default' => isset($data['TKD']) && isset($data['TKD']['keywords']) ? $data['TKD']['keywords'] : ''
])
@include('ui::form.textarea', [
  'label' => '页面描述',
  'name' => 'TKD[description]',
  'extra' => [],
  'placeholder' => '页面描述',
  'helper' => '若留空则表示采用网站自己的规则生成',
  'data' => [],
  'default' => isset($data['TKD']) && isset($data['TKD']['description']) ? $data['TKD']['description'] : ''
])