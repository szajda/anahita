<?php defined('KOOWA') or die ?>
<?php
    $other_subjects  = is_array($subject) ? array_slice($subject, 1) : array(); 
    $subject         = is_array($subject) ? array_shift($subject) : $subject;     
?>
<div class="an-story an-entity an-record an-removable">
	<div class="clearfix">
	    <div class="entity-portrait-square">
	        <?= @avatar($subject) ?>
	    </div>     
    
    	<div class="entity-container">
    		<?php if( !empty($title)): ?>
    		<h4 class="story-title">
    			<?= $title ?>
    		</h4>    		
    		<?php else: ?>
    		<h4 class="entity-author">
    			<?= @name($subject) ?>
    		</h4>
    		<?php endif; ?>

    		<ul class="an-meta inline">
    			
    			<?php if ( false && count($other_subjects) > 0 ) : ?>
    		    	<?= sprintf('and %s others', count($other_subjects))?> 
    			<?php endif;?>
    			
    			<li><?= @date($timestamp) ?></li> 
    			
    			<?php if( !$item->aggregated() && $item->target->id != $item->subject->id ): ?>
				<li>
					<a href="<?= @route($item->target->getURL()) ?>"><?= @name($item->target) ?></a>
				</li>
				<?php endif; ?>
    		</ul>
    	</div>
    </div>
    
    <?php if( !empty($item->object->title)): ?>
    <h3 class="entity-title">
    	<a href="<?= @route($item->object->getURL()) ?>">
    		<?= $item->object->title ?>
    	</a>
    </h3>
    <?php endif; ?>
    
    <?php if ( !empty($body) ) : ?>
    <div class="story-body">
    	<?= $body ?>
    </div>
    <?php endif; ?>
        
    <div class="entity-meta">
        
    	<?php
           $votable_item = null;               
           if ( !$item->aggregated() && $item->object && $item->object->isVotable() ) { 
                $votable_item = $item->object;
           }
        ?>
        
        <?php if ( $votable_item ) : ?> 
        <div class="vote-count-wrapper" id="vote-count-wrapper-<?= $votable_item->id ?>">
            <?= @helper('ui.voters', $votable_item); ?>
        </div>
        <?php endif; ?>
    </div>
        
    <div class="entity-actions">    
    	<?php $can_comment = $commands->offsetExists('comment') ?>
        <?= @helper('ui.commands', $commands)?>
    </div>      
   
    
    <div id="<?= 'story-comments-'.$item->id?>" class="story-comments an-comments">
		<?php if ( !empty($comments) || $can_comment ) : ?>
	    <?= @helper('ui.comments', $item->object, array('comments'=>$comments, 'can_comment'=>$can_comment, 'pagination'=>false, 'show_guest_prompt'=>false, 'truncate_body'=>array('consider_html'=>true, 'read_more'=>true))) ?>
	    <?php endif;?>
	    
	    <?php if( !empty($comments) && $can_comment ): ?>
	    <div class="comment-overtext-box">  
	    	<span class="action-comment-overtext" storyid="<?=$item->id?>">
	        	<?= @text('COM-STORIES-ADD-A-COMMENT') ?>
	        </span>
	    </div>
	    <?php endif; ?>
	</div>
</div>