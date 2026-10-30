<?php

return [
    'title' => '자동 백업',
    'help' => '실패한 백업이 성공한 복구 지점을 대체하는 일은 없습니다.',
    'enabled' => '자동 백업 켜짐',
    'interval' => '간격 · 분',
    'points' => '복구 지점',
    'only_used' => '서버가 실행되지 않은 동안 건너뛰기',
    'only_used_help' => '마지막 백업 이후 시작되지 않은 오프라인 서버는 다시 백업하지 않습니다.',
    'no_storage' => '백업 저장소가 아직 설정되지 않았습니다. 관리자에게 Pelican에서 백업 호스트를 설정해 달라고 요청하세요.',
    'paused' => '자동 백업이 Simplify 설정에서 일시 중지되어 있습니다.',
    'uses' => '자동 설정 사용: :interval, 복구 지점 :points개:unused.',
    'every_hours' => ':count시간마다',
    'every_minutes' => ':count분마다',
    'skipped_unused' => '서버가 실행되지 않은 동안 건너뜀',
    'name' => '자동',
    'panel' => '자동 백업',
    'panel_help' => '설정한 백업 정책을 기존 Pelican 스케줄러로 실행합니다. 끄면 정책을 삭제하지 않고 일시 중지합니다.',
    'default_interval' => '새 서버 백업 간격(분)',
    'default_interval_help' => '새로 만든 서버의 자동 백업 시작 간격입니다.',
    'default_points' => '새 서버 복구 지점 수',
    'default_points_help' => '보관할 성공한 자동 백업 수입니다.',
];
