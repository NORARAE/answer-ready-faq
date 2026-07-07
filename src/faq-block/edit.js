/**
 * Answer-Ready FAQ — editor experience.
 *
 * Editorial model: a repeater of question/answer pairs managed as a
 * single `faqs` array attribute. Every mutation is expressed as a pure
 * transformation of that array (add, update, move, remove), which keeps
 * undo/redo and multi-user editing predictable.
 *
 * Accessibility in the editor: reorder/remove controls are real
 * buttons with discernible labels; the item being edited is a
 * fieldset-like group with a visible index so screen-reader users can
 * orient within long lists.
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	ToggleControl,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { plus, arrowUp, arrowDown, trash } from '@wordpress/icons';

/** Formats an answer may contain — enforced again server-side in render.php. */
const ANSWER_FORMATS = [ 'core/bold', 'core/italic', 'core/link', 'core/code' ];

/**
 * Immutable helpers. Each returns a new array; attributes are never
 * mutated in place.
 *
 * @param {Array}  faqs  Current list of Q&A pairs.
 * @param {number} index Index of the item being updated.
 * @param {Object} patch Partial item to merge into the existing one.
 * @return {Array} A new faqs array.
 */
const updateItem = ( faqs, index, patch ) =>
	faqs.map( ( faq, i ) => ( i === index ? { ...faq, ...patch } : faq ) );

const removeItem = ( faqs, index ) => faqs.filter( ( _, i ) => i !== index );

const moveItem = ( faqs, from, to ) => {
	if ( to < 0 || to >= faqs.length ) {
		return faqs;
	}
	const next = [ ...faqs ];
	const [ moved ] = next.splice( from, 1 );
	next.splice( to, 0, moved );
	return next;
};

export default function Edit( { attributes, setAttributes } ) {
	const { heading, faqs, emitSchema } = attributes;
	const blockProps = useBlockProps( { className: 'answer-ready-faq' } );

	const setFaqs = ( next ) => setAttributes( { faqs: next } );

	const addFaq = () => setFaqs( [ ...faqs, { question: '', answer: '' } ] );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Structured data', 'answer-ready-faq' ) }
				>
					<ToggleControl
						label={ __(
							'Emit FAQPage JSON-LD',
							'answer-ready-faq'
						) }
						help={ __(
							'Outputs a schema.org FAQPage graph so search engines and AI answer engines can read this content. Disable if another block on the page already emits FAQ schema.',
							'answer-ready-faq'
						) }
						checked={ emitSchema }
						onChange={ ( value ) =>
							setAttributes( { emitSchema: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<RichText
					tagName="h2"
					className="answer-ready-faq__heading"
					placeholder={ __(
						'FAQ heading (optional)…',
						'answer-ready-faq'
					) }
					value={ heading }
					onChange={ ( value ) =>
						setAttributes( { heading: value } )
					}
					allowedFormats={ [] }
				/>

				{ faqs.length === 0 && (
					<p className="answer-ready-faq__empty">
						{ __(
							'No questions yet. Add your first question and answer below.',
							'answer-ready-faq'
						) }
					</p>
				) }

				{ faqs.map( ( faq, index ) => (
					<div
						key={ index }
						className="answer-ready-faq__editor-item"
						role="group"
						aria-label={ sprintf(
							/* translators: %d: position of the question in the list. */
							__( 'Question %d', 'answer-ready-faq' ),
							index + 1
						) }
					>
						<Flex
							className="answer-ready-faq__editor-toolbar"
							justify="space-between"
						>
							<FlexItem>
								<span className="answer-ready-faq__editor-index">
									{ sprintf(
										/* translators: %d: position of the question in the list. */
										__( 'Q%d', 'answer-ready-faq' ),
										index + 1
									) }
								</span>
							</FlexItem>
							<FlexItem>
								<Button
									icon={ arrowUp }
									label={ __(
										'Move up',
										'answer-ready-faq'
									) }
									disabled={ index === 0 }
									onClick={ () =>
										setFaqs(
											moveItem( faqs, index, index - 1 )
										)
									}
									size="small"
								/>
								<Button
									icon={ arrowDown }
									label={ __(
										'Move down',
										'answer-ready-faq'
									) }
									disabled={ index === faqs.length - 1 }
									onClick={ () =>
										setFaqs(
											moveItem( faqs, index, index + 1 )
										)
									}
									size="small"
								/>
								<Button
									icon={ trash }
									label={ __(
										'Remove question',
										'answer-ready-faq'
									) }
									onClick={ () =>
										setFaqs( removeItem( faqs, index ) )
									}
									isDestructive
									size="small"
								/>
							</FlexItem>
						</Flex>

						<RichText
							tagName="p"
							className="answer-ready-faq__editor-question"
							placeholder={ __(
								'Question…',
								'answer-ready-faq'
							) }
							value={ faq.question }
							onChange={ ( value ) =>
								setFaqs(
									updateItem( faqs, index, {
										question: value,
									} )
								)
							}
							allowedFormats={ [] }
						/>
						<RichText
							tagName="p"
							className="answer-ready-faq__editor-answer"
							placeholder={ __( 'Answer…', 'answer-ready-faq' ) }
							value={ faq.answer }
							onChange={ ( value ) =>
								setFaqs(
									updateItem( faqs, index, { answer: value } )
								)
							}
							allowedFormats={ ANSWER_FORMATS }
						/>
					</div>
				) ) }

				<Button
					className="answer-ready-faq__add"
					icon={ plus }
					variant="secondary"
					onClick={ addFaq }
				>
					{ __( 'Add question', 'answer-ready-faq' ) }
				</Button>
			</section>
		</>
	);
}
