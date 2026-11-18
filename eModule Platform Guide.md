# eModule Platform Guide

Nov 18, 2026 · @Eljames

## Overview

eModule is a gamified reading platform. Teachers build **modules** (reading adventures) made of **lectures** and **quizzes**, then hand them to students through **classrooms** or a module's own code. Students read, answer, earn XP and coins, and climb a classroom leaderboard.

There are two roles, set at sign-up (`teacher` or `student`). Each role has its own portal; a student who opens a teacher page is sent to the student dashboard, and the reverse.

| Area | Teacher portal | Student portal |
| --- | --- | --- |
| Home | Dashboard (`/teachers/dashboard`): modules, class filter | Welcome page (`/students/welcome`) |
| Classes | Classes (`/teachers/classes`): create rooms, assign modules, roster | Classes (`/students/classes`): joined rooms and Code Adventures |
| Content | Module journey map, Lecture builder, Quiz Studio | Module progress map, lecture reader, quiz arena |
| Assessment | Grading queue (`/teachers/grading`), Quiz results | Instant results; pending review for written answers |
| Motivation | Students page (`/teachers/students`) | Ranking, Shop, Progress, Settings (theme) |

The content hierarchy is: **Classroom → Module → milestones (Lectures and Quizzes) → items**. A module can belong to several classrooms, and a classroom can hold several modules.

## Creating classes

A classroom ("room") groups students and gives them every module assigned to it through one 6-digit **Quest Code**.

1. Go to **Classes** (`/teachers/classes`) and click **+ NEW ROOM** (or **SUMMON NEW CLASS ROOM** when you have none).
2. Fill in the form, then click **SUMMON REALM**.
3. A success message shows the room's Quest Code. Share that code with your students.

| Field | Required | Rules | Default if blank |
| --- | --- | --- | --- |
| Class / Room Name | Yes | Up to 100 characters | — |
| Grade level | No | Up to 50 characters | All Grades • Reading Legends |
| Realm Environment & Lighting (theme) | No | forest, ocean, cosmic or volcano | forest |
| Room Mascot Emblem (icon) | No | An emoji, up to 30 characters | 🏫 |
| Description | No | Up to 1,000 characters | — |
| Max Capacity | No | 1 to 200 students | 45 |
| Modules | No | Any of your own modules, assigned at creation | — |

**The Quest Code.** It is generated automatically: 6 random digits (000000–999999), checked to be unique across all classrooms. You cannot type your own.

**Managing a room after creation:**

- **Assign a module** with **+ Assign**. Only modules you own can be assigned; assigning the same module twice does nothing.
- **Remove a module** from the room. Students lose access through that room, but their past attempts remain.
- **Roster**: view enrolled students and remove one. Removal deletes only the enrollment, not the student's XP or history.

**How a student joins.** The student enters the 6-digit code on their Classes page. The app checks the code in this order:

1. An **active classroom** with that code. The join fails if the student is already in it, or if the room is at Max Capacity.
2. Otherwise, a **published module** with that join code (a "Code Adventure", see Creating modules). Draft or archived modules never match.
3. No match: "No published adventure or classroom found with code …".

The code must be exactly 6 digits. Joining a room gives the student every **published** module assigned to it; a draft module in the room stays hidden until you publish it.

## Creating modules

A module is one reading adventure: a themed journey map of lectures and quizzes, with a one-time XP reward (the **Treasure Vault**) for finishing everything.

1. From the Dashboard, click **Create New Module** (`/teachers/create/module`). If you arrived from a class, the form shows a **Target Classroom** and the module is assigned to it on save.
2. Fill in **Module Identity & Biome Theme**, then **Cover Art & Quest Rewards**.
3. Choose **Draft** or **Live** and save. You land on the Dashboard; open the module to reach its journey map.

| Field | Required | Rules | What it does |
| --- | --- | --- | --- |
| Module Title | Yes | Up to 255 characters | Also builds the URL slug (`my-title`, `my-title-1` if taken) |
| Grade level | No | Up to 255 characters | Label on module cards |
| Description | No | Up to 2,000 characters | Shown to students on the module card |
| Biome Theme | Yes | forest, ocean, cosmic, volcano | Colours and art of the journey map |
| Icon | Yes | Emoji, up to 32 characters | Module badge |
| Cover Banner | No | JPG, PNG or WEBP, max 5 MB, 16:9 suggested | Card and map header image |
| Guild XP Reward (`xp_bonus`) | Yes | 0 to 100,000 | Treasure Vault XP, paid once per student |
| Boss Challenge (`boss_name`) | No | Up to 120 characters | Name of the final "boss" on the map |
| Max students | No | 1 to 500 | Cap for students joining by the module's own code |
| Status | Yes | Draft or Live (published); archived via status update | Controls visibility |

**Status rules.**

- **Draft**: only you see it. Students cannot join it by code, and classrooms do not show it.
- **Live (Published)**: visible to students in any classroom it is assigned to, and joinable by its own code. Publishing stamps a published date.
- **Archived**: hidden from students, like Draft.
- The status toggle on the module flips Published ↔ Draft. Moving back to Draft clears the published date.

**Two ways students get a module.**

- **Through a classroom**: assign the module to a room (Creating classes). Every student in that room sees it.
- **By the module's own Join Code**: each module gets its own unique 6-digit code at creation. A student who enters it is enrolled directly, and the module appears under **Code Adventures**. The module's Max students cap applies here.

**Order of milestones.** Lectures and quizzes share one sequence on the journey map. Each new lecture or quiz is placed after the last milestone, whichever kind it was.

**AI help.** The module form, lecture builder and quiz editors call Gemini (`/teachers/ai/*`) to draft descriptions, lecture text, word meanings and quiz items. The **status** endpoint tells the page whether AI is configured; if it is not, the buttons are unavailable and everything can still be typed by hand.

## Adding lectures

A lecture is a reading milestone on the map. You create it in two steps: the **lecture** (title, XP, publish switch), then its **pages** in the Interactive Story Studio.

**Step 1 — Create the lecture.** On the module's journey map, add a lecture (`Create New Reading Lecture`).

| Field | Required | Rules |
| --- | --- | --- |
| Lecture Title | Yes | Up to 255 characters; builds the slug, unique within the module |
| Short Objective / Summary | No | Up to 2,000 characters |
| Lecture Body & Teaching Notes | No | Free text |
| Estimated duration | No | 1 to 600 minutes |
| XP Reward | Yes | 0 to 100,000 |
| Publish Milestone Immediately | No | On by default; when on, students see it on their map |

Click **Create Lecture Milestone**. The lecture is placed at the end of the module's milestone sequence, and the right panel previews where it lands on the S-curve map.

**Step 2 — Build the pages.** Open the lecture's content builder (Interactive Story Studio). Each page is a content block; pages are shown in the order you add them (**+ New Page**).

| Page part | How to add it | Limits |
| --- | --- | --- |
| Text passage | Type on the Studio Canvas; title the page | No length limit |
| Image, audio, video | Drag and drop, **Uploads**, or **Or Insert by Image / Video URL** | Page media up to 50 MB; inline media up to 2 GB (image, audio, video only) |
| Document | Attach as the page's media file | 50 MB |
| Caption and alt text | Fields under the media | Caption 1,000, alt text 255 characters |
| Paper theme | **PAPER** picker | classic, parchment, emerald, sky, lined |
| Layouts and callouts | **Story Layouts** / **Story Callout Elements** (Moral of the Story, Guiding Reading Question, Character Speaks, Think & Reflect, Key Takeaway) | — |
| Word meanings | Select a word → **+ Add Word Meaning** → word, pronunciation/part of speech, student-friendly definition, badge colour (or **AI Auto-Define**) | — |
| Read-aloud narration | **Full-Page Read-Aloud Narration Audio** | 50 MB audio file |

The **Gemini AI Story Generator** (**Auto-Write Story & Vocab**) drafts a chapter with highlighted vocabulary from a prompt, then inserts it onto the canvas for you to edit. Click **Publish Page to Lecture** to save each page. Undo and Redo work on the canvas.

**What students get.** Students read the pages on the same paper theme, tap highlighted words to see their meaning, and play narration. When they finish, they mark the lecture complete and earn its XP Reward **once**; re-reading pays nothing more.

## Quizzes: Quiz Studio and common rules

All six quiz types are built in one editor, the **Quiz Studio** (`/teachers/modules/{module}/quizzes/studio`). Four are graded instantly by the app; Evaluation and Reflection wait for you to grade them.

**Building a quiz.**

1. Open a module and go to **Quiz Studio**.
2. Pick a format under **Formats** (6 Quiz Formats). **Starter Presets** fill a sample story and items you can edit; **Fill Sample** does the same for the current slide.
3. Add the story passages (**Story Passages**), then build items one slide at a time in the **SLIDE DECK**.
4. Set **Quiz & XP Rules**, keep **Publish immediately on Module Map** on or off, and save. The Studio lists problems under "Please review the following before publishing".
5. To change a saved quiz, open it from **Module Quizzes** ("Editing Existing Quiz"). Saving replaces all its items and passages with the new set.

**Settings shared by every quiz type.**

| Setting | Rules | Default | Effect |
| --- | --- | --- | --- |
| Title | Required, up to 255 characters | — | Name on the map; builds the slug |
| Description | Up to 2,000 characters | — | Shown before the quiz starts |
| Story Passages (contexts) | Any number; each a scenario plus an optional Reading Clue (1,000 characters) | — | Shown beside the items |
| XP Completion Bounty (`xp_reward`) | 10 to 50,000 | — | Full XP at 100%; scaled by score |
| Passing Score Threshold | 1 to 100% | 70% if missing | Pass or fail line |
| Stars Per Correct Answer | 0 to 100 | 1 | Auto-graded types only |
| Gems Per Correct Answer | 0 to 500 | 10 | Auto-graded types only |
| Time limit | 10 to 7,200 seconds, optional | None | Countdown; auto-submits at 0 |
| Publish immediately | On or off | On | Off hides the quiz from students |
| Points per item | 1 to 1,000 | — | Weight of each item in the score |

**How a submission is scored.** The server grades every submission; the browser cannot set its own score.

```latex
\text{Score \%} = \frac{\text{points earned}}{\text{points possible}} \times 100
```

- **Pass** when Score % ≥ Passing Score Threshold.
- **XP** = Score % × XP Completion Bounty, rounded. 80% on a 200 XP quiz pays 160 XP.
- **Auto-graded types** (Multiple Choice, Color the Conflict, Conflict Map, Matching Type): Stars = correct items × Stars Per Correct; Gems = correct items × Gems Per Correct. An item is right or wrong; there is no partial credit within an item.
- **Teacher-graded types** (Evaluation, Reflection): the attempt is saved as **Pending review** with 0 points. Rewards are paid after you grade it (see Evaluation).

**One attempt per quiz.** Once a quiz is completed or pending review, the student cannot submit it again; they see their earlier result. Hints and retry feedback inside the quiz help before submitting, not after.

**Deleting a quiz** removes it and its attempts from the module, along with any Conflict Map pictures no other quiz uses.

## Multiple Choice

Students pick the right option(s) for each question; the app grades it instantly.

**Build each question slide:**

1. Type the **Question Prompt** (up to 1,000 characters).
2. Choose the **Answer Mode**: **Single Choice** (one correct answer) or **Multiple Correct Answers** (multi-select).
3. Add 2 to 6 options (up to 500 characters each). Mark which are correct; each option can carry its own feedback (500 characters).
4. Optional: a passage excerpt for this question, a **Student Hint** (500), an **Answer Explanation** (1,000).
5. Set **Points** (1 to 1,000).

**Grading rule.** A question is correct only when the student's selection **exactly equals** the set of correct options. On a multi-select question with correct answers A and C, picking A alone, or A, C and D, scores 0. A blank answer scores 0.

**Limits:** at least 1 question; 2 to 6 options per question.

**Tip.** For multi-select, say so in the prompt ("Choose all that apply"). Students cannot earn part of the points.

## Color the Conflict

Students read short story excerpts on flashcards and "colour" each one with its type of conflict.

There are four fixed conflict types, each with its own colour:

| Colour | Conflict type | Meaning |
| --- | --- | --- |
| Red | Character vs. Character | Interpersonal conflict between individuals |
| Blue | Character vs. Society | Rules, laws, customs and community institutions |
| Green | Character vs. Nature | Weather, wilderness and environmental forces |
| Amber | Character vs. Self | Internal doubts, fear and moral dilemmas |

**Build each card slide:**

1. Type the **Story Excerpt on Flashcard** (up to 3,000 characters).
2. Pick the correct conflict type.
3. Optional: **Hint**, **Explanation** (shown when right), and **Retry Feedback** (`feedback_incorrect`, shown when wrong; falls back to the hint). Each up to 1,000 characters.
4. Set **Points** (1 to 1,000).

**Grading rule.** A card is correct when the colour chosen matches its conflict type. No answer scores 0.

**Limits:** 1 to 20 cards per quiz.

## The Conflict Map

Students drag event cards from an **Event Bank** into conflict-type **drop zones** on a map. It tests the same four conflict types as Color the Conflict, but with sorting, and cards can be pictures.

**1. Set up the drop zones (2 to 6).** Each zone has:

- a conflict type (one of the four above), which is what grading checks;
- a **Label** (up to 255 characters) and an optional subtitle (500);
- a colour theme (defaults to blue).

**2. Add event cards (1 to 30).** Each card has:

| Field | Rules |
| --- | --- |
| Card Badge Label (`card_letter`) | Optional, up to 20 characters; defaults to "Card A", "Card B"… by position |
| Event text | Up to 2,000 characters; required unless the card has a picture |
| Picture | Optional; JPG, PNG, WEBP or GIF, max 5 MB |
| Target Zone | Required; the conflict type the card belongs in |
| Hint, Explanation | Optional, 1,000 characters each |
| Points | 1 to 1,000 |

**Picture cards in bulk.** Use **+ Images** or drag several pictures at once (**Upload Multiple Images**): up to 30 files per upload, 5 MB each. The Studio makes one card per picture, then lets you set each card's zone ("Quickly set which conflict bucket each picture belongs to"). A card may have text, a picture, or both.

**Grading rule.** A card is correct when the student drops it into a zone whose conflict type equals the card's Target Zone type. Unplaced cards score 0.

**Watch out.** Pictures belong to the module they were uploaded for. If you see "This event card picture is missing. Please upload it again.", re-upload the picture in this module's Studio. Make sure every card's target type has a zone; a card whose type has no zone can never be scored correct.

## Matching Type

Students match each description in **Column A** to a lettered answer in **Column B**.

**1. Build Column B (the choices), 2 to 26.** Each choice has a letter (A, B, C…; up to 10 characters) and its text (up to 500 characters).

**2. Build Column A (the items), 1 to 30.** Each item has:

- a **Description** prompt (up to 2,000 characters);
- the **correct choice letter** from Column B;
- optional **Hint** and **Explanation** (1,000 each);
- **Points** (1 to 1,000).

**Grading rule.** An item is correct when the letter picked equals its correct letter (upper and lower case count the same). Blank scores 0.

**Tips.**

- Add a few more choices than items as distractors, so the last match is not a giveaway.
- Several items may point at the same letter; nothing forces one-to-one matching.

## Evaluation

A two-step critical-thinking item: the student takes a **stance**, then completes a sentence that justifies it. You grade it by hand.

**Build each item slide (1 to 20 items):**

| Field | Required | Rules | Purpose |
| --- | --- | --- | --- |
| Section Title | Yes | 255 characters | Heading of the slide |
| Conflict Theme Tag | No | 100 characters | Labels which conflict it is about |
| Critical Thinking Question Prompt | Yes | 2,000 characters | The question to judge |
| Step 1 prompt | No | 255 characters | Instruction over the stance buttons |
| Step 1: Student Stance Choice Buttons | Yes | 2 to 10 options, 255 characters each | The stances to choose from |
| Stance value (per option) | No | 100 characters | Word put into `[stance]`; falls back to the option text |
| Step 2: Guided Sentence Template | Yes | 2,000 characters | Sentence with `[stance]` and `[blank]` tokens |
| Hint | No | 1,000 characters | Shown on the slide |
| Points | Yes | 1 to 1,000 | Maximum you can award |

Use **+ Insert \[stance\]** and **+ Insert \[blank\]** to place tokens. Example: `I believe Mira was [stance] because she prioritized the [blank] of her community.` The student's chosen stance fills `[stance]`; they type into each `[blank]`.

**Word Helpers** are chips of useful words (up to 100 characters each) shown to students as vocabulary support, e.g. courage, community, responsibility.

**What happens on submit.** Nothing is auto-scored, even if you mark an option as correct. The attempt becomes **Pending review** with 0 points, and the student sees "Your teacher will review and grade your work." An item counts as answered once a stance is picked and at least one blank has text.

**Grading (applies to Evaluation and Reflection).**

1. Open **Grading** (`/teachers/grading`) or the quiz's **Results** page. The sidebar badge shows how many submissions wait.
2. For each answer, enter points from 0 to the item's points (higher values are capped), plus an optional note (1,000 characters).
3. Add overall feedback for the student (2,500 characters) and save.

On save, the attempt becomes **Completed** and the rewards are computed:

| Result | Rule |
| --- | --- |
| Score % | points awarded ÷ points possible × 100 |
| Passed | Score % ≥ Passing Score Threshold |
| Stars | 3 at 90% or more; 2 at or above the passing threshold; 1 at 40% or more; else 0 |
| XP | Score % × XP Completion Bounty |
| Gems | None: Gems Per Correct is not paid on hand-graded quizzes |

Any answer given more than 0 points is marked correct. The student's totals are then fully recalculated, so the ranking updates at once.

## Reflection

An open journal response to one or more prompts. You grade it by hand, exactly as for Evaluation.

**Build each prompt (1 to 20):**

| Field | Required | Rules | Purpose |
| --- | --- | --- | --- |
| Reflection Question / Journal Prompt | Yes | 3,000 characters | What the student writes about |
| Maximum words | Yes | 10 to 5,000 | Upper word goal |
| Minimum Words | No | 1 to 5,000 | Lower word goal |
| Guiding Hint | No | 1,000 characters | Shown as "Guiding Question" |
| Starter Sentence Scaffold | No | 1,000 characters | Sentence the student can insert to begin |
| Points | Yes | 1 to 1,000 | Maximum you can award |

**What students see.** A word counter with the goal ("Goal: 10–150 words") and how many more words are needed. Checking an answer under the minimum shows it as too short. If you leave Minimum Words blank, the screen uses 10 as the goal.

**On submit** the attempt is **Pending review**. Each answer is stored with its word count, so you can see effort at a glance while grading. Rewards follow the grading table in the Evaluation section.

**Tip.** Word limits guide the student on screen; they are not hard grading rules. Mention in your feedback when a response misses the length you asked for.

## Student ranking: how the top students are chosen

The leaderboard ("This Week's Star Readers") is a **weekly race for stars**. The student with the most stars earned this week is #1; ties go to the student with more all-time XP, then to alphabetical order. The board empties every Monday.

**Who is compared.** A student sees the ranking of one classroom they belong to (they can switch between their rooms). A student in no classroom is ranked against every student on the platform.

**The sort order, in full.** Every student in the room is sorted by these keys, each one only breaking ties in the one before:

| Order | Key | Direction | Notes |
| --- | --- | --- | --- |
| 1 | Weekly stars | Highest first | Stars from quiz attempts **completed** this week |
| 2 | Total XP (all time) | Highest first | Lectures + quizzes + Treasure Vault bonuses |
| 3 | Name | A to Z | Final tie-break so the order is stable |

&#91;embedded content: weekly ranking order · 1 filter, 3 sort keys\]

Only students with at least one star this week are ranked onto the board; XP and name matter only when stars are tied.

**What counts as a weekly star.**

- The week runs **Monday 00:00 to Sunday 23:59:59, UTC** (the server's time zone). The page shows a countdown to the next reset.
- Each completed quiz attempt adds its stars on the day it became **completed**. For auto-graded quizzes, that is the submit time. For Evaluation and Reflection, it is the time **you grade it**, so grading on Monday puts those stars into the new week.
- Stars come only from quizzes. Lectures and the Treasure Vault add XP, which matters only as the tie-breaker.
- Auto-graded quiz stars = correct items × that quiz's Stars Per Correct. Hand-graded quiz stars = 0 to 3 by score (see Evaluation).

**Who appears on the board.** Only the **top 5** students with **at least 1 weekly star** are shown. A student with 0 stars this week is never listed, however much XP they have, so on Monday morning the board is empty.

**What each student sees about themselves.**

- **In the top 5**: their rank and a message such as "You're #1! Champion reader!".
- **Outside the top 5**: no exact rank (to avoid discouraging low ranks), but their weekly stars, level and XP, plus **how many stars they need** to get on the board:
  - board not full yet (fewer than 5 students with stars) → 1 star;
  - board full → (5th place's weekly stars − my weekly stars + 1), minimum 1.
- Classmates' email addresses are never sent to the ranking page.

**Worked example.** One room, Wednesday:

| Student | Weekly stars | Total XP | Board position |
| --- | --- | --- | --- |
| Ana | 12 | 900 | #1 |
| Ben | 12 | 650 | #2 (tied stars, less XP) |
| Cara | 9 | 4,000 | #3 |
| Dan | 6 | 300 | #4 |
| Eli | 6 | 300 | #5 (tied stars and XP; "Dan" sorts before "Eli") |
| Fay | 4 | 2,500 | Not shown; needs 6 − 4 + 1 = **3 stars** |
| Gus | 0 | 9,000 | Not shown; no stars this week |

**Teacher view.** The **Students** page (`/teachers/students`) uses the same order for your roster, filterable by class. Unlike the student board, it lists **every** student, including those on 0 stars.

**Rank badges.** Positions carry badges: #1 Champion, #2 Vanguard, #3 Explorer, #4–10 Elite, #11+ Pioneer.

**Fairness tip.** Because Stars Per Correct is set per quiz, a quiz set to 5 stars per correct answer moves the weekly race far more than one set to 1. Keep it the same across quizzes in a class unless you want some quizzes to count more.

## Gamification: XP, levels, stars, gems and the shop

Students earn three currencies. **XP** sets their level, **stars** drive the weekly ranking, and **gems** buy cosmetics in the shop.

| Currency | Earned from | Paid how often | Used for |
| --- | --- | --- | --- |
| XP | Lecture XP Reward; quiz score % × XP Bounty; module Treasure Vault | Once per lecture, once per quiz, once per module | Level; ranking tie-break |
| Stars | Quizzes only (correct items × Stars Per Correct, or 0–3 when hand-graded) | Once per quiz | Weekly ranking |
| Gems | Auto-graded quizzes only (correct items × Gems Per Correct) | Once per quiz | Shop purchases |

**Never paid twice.** Re-reading a lecture pays 0 XP. Quiz rewards keep only the student's best result per quiz (and a quiz can be submitted once anyway). The Treasure Vault can be claimed once per module; a second claim returns the first one.

**Treasure Vault (module bonus).** When a student has completed **every** lecture and quiz in a module, the vault on the map unlocks and pays the module's Guild XP Reward. A quiz still pending review does not count as completed, so students may need to wait for your grading. A module with no lectures or quizzes has no vault to open.

**Levels.** Level comes from total XP. Levels 1–10 follow the table; after level 10, every further 1,000 XP is one more level (titled "Champion Tier N").

| Level | Total XP needed | Title | Badge |
| --- | --- | --- | --- |
| 1 | 0 | Novice Reader | Sprout |
| 2 | 200 | Trail Scout | Scout |
| 3 | 500 | Meadow Pathfinder | Explorer |
| 4 | 900 | Forest Navigator | Navigator |
| 5 | 1,400 | Rune Scholar | Scholar |
| 6 | 2,000 | Chronicle Seeker | Seeker |
| 7 | 2,700 | Lorekeeper | Lorekeeper |
| 8 | 3,500 | Master Storyweaver | Storyweaver |
| 9 | 4,400 | Legendary Pioneer | Pioneer |
| 10 | 5,400 | Grand Champion of Lore | Champion |
| 11+ | 6,400, 7,400, … | Champion Tier 11, 12, … | Champion |

The HUD bar shows XP inside the current level and the percentage to the next.

**The Gem Bazaar (Shop).** Gem balance = gems ever earned − gems ever spent. Each purchase checks, in order: the student's level meets the item's minimum level; they do not already hold the maximum quantity; they have enough gems. Owned cosmetics can be **equipped** (one per slot).

| Shop category | What it changes | Items now stocked |
| --- | --- | --- |
| Console (header backgrounds) | The student's HUD header | Sunset Meadow, Ocean Breeze (L1); Candy Clouds (L2); Starlight Night (L3); Aurora Sky (L4); Cosmic Voyage (L6) |
| Background (trail themes) | The expedition map trail | Enchanted Forest, Sunny Beach (L1); Candy Land (L2); Frosty Peaks (L3); Space Odyssey (L5) |
| Avatar | Student avatar | None stocked yet |
| Booster | Used inside quizzes; cannot be equipped | None stocked yet |

All stocked items currently cost **0 gems** (marked "while testing" in the catalog), so only the level gate limits them. Set real prices before launch.

**Settings.** Students can also pick a visual world theme on their Settings page (Toybox, Luca Coast, Memory Orb, Marigold). It is cosmetic and free.

## Student journey and teacher monitoring

A student goes from joining a room to climbing the weekly board in seven steps:

1. **Sign up** as a student and land on the Welcome page.
2. **Join** with a 6-digit Quest Code on the Classes page (a classroom code, or a module's own code for a Code Adventure).
3. **Open a module.** Its expedition map shows milestones in order. The first milestone not yet completed is the current step; later islands show as locked until the ones before are done.
4. **Read a lecture** and mark it complete: XP is paid once.
5. **Take a quiz.** Hints and Reading Clues can be toggled on. Auto-graded quizzes show the result, stars, gems and XP at once. Evaluation and Reflection show "awaiting teacher grading".
6. **Finish every milestone** to open the Treasure Vault and claim the module's bonus XP.
7. **Check Ranking, Progress and Shop** to see their weekly position, level and cosmetics.

**What teachers can monitor.**

| Page | Shows |
| --- | --- |
| Dashboard | Your modules, filterable by class; create modules from here |
| Classes | Each room's Quest Code, student and module counts, roster, assigned modules |
| Students | Every student in your rooms, ranked by this week's stars, with XP, level, quizzes passed, lectures completed |
| Grading | All submissions pending review across your quizzes; the sidebar badge counts them |
| Quiz Results | Per quiz: attempts, pending and graded counts, pass count, average, highest and lowest score, and each student's answers |

## Tips, limits and troubleshooting

**Before students start.**

- Publish both the **module** and each **quiz/lecture**. A draft module hides everything in it, even published quizzes.
- Set real **shop prices**; every stocked item costs 0 gems today.
- Pick one **Stars Per Correct** value for the whole class so the weekly race is fair.
- Finish editing a quiz before students take it. Saving a quiz rebuilds all its items, so answers already submitted may no longer line up with the new items in Results. To change a quiz students have taken, create a new one.

**Behaviour that surprises people.**

- **No retakes.** A quiz can be submitted once per student; there is no retake button in the app.
- **Timer runs out** → the quiz submits automatically with whatever is answered.
- **Stars and Gems per correct** are ignored on Evaluation and Reflection; hand-graded quizzes earn 0–3 stars and no gems.
- Marking an Evaluation stance option as "correct" does not auto-grade it; you still award the points.
- **Weekly reset is Monday 00:00 UTC.** In UTC+8, that is Monday 08:00 local time.
- **Deleting a quiz** deletes its attempts, so those stars leave the weekly board at once. The student's total XP, stars and gems keep the old value until their profile is next recalculated, which happens when you grade one of their submissions.
- **Removing a student** from a room keeps their XP, stars and gems; they just leave that room's ranking.

**Limits at a glance.**

| Item | Limit |
| --- | --- |
| Students per classroom | 1–200 (default 45) |
| Students per module (by module code) | 1–500, or unlimited |
| Module cover image | 5 MB, JPG/PNG/WEBP |
| Lecture page media / narration | 50 MB each |
| Inline lecture media | 2 GB, image/audio/video only |
| Multiple Choice | ≥ 1 question, 2–6 options each |
| Color the Conflict | 1–20 cards |
| Conflict Map | 2–6 zones, 1–30 events, pictures 5 MB, 30 per upload |
| Matching Type | 2–26 choices, 1–30 items |
| Evaluation | 1–20 items, 2–10 stances each |
| Reflection | 1–20 prompts, 10–5,000 max words |
| Quiz time limit | 10 s – 2 h |

**Common error messages.**

| Message | Cause | Fix |
| --- | --- | --- |
| No published adventure or classroom found with code … | Wrong code, inactive room, or module still Draft | Check the code; publish the module |
| This classroom is at full capacity | Room reached Max Capacity | Raise capacity or open a second room |
| You have already joined this classroom / module | Student is already enrolled | Nothing to do |
| You have already completed this quiz / awaiting teacher grading | One-attempt rule | Grade the pending submission |
| Finish every island quest in this module to unlock the treasure vault | A milestone is incomplete or still pending review | Complete it, or grade it |
| This event card picture is missing. Please upload it again. | Picture was uploaded for another module | Re-upload in this module's Studio |
| The file failed to upload. Check post\_max\_size and upload\_max\_filesize in php.ini. | Server upload limits are lower than the file | Raise those PHP settings |
| Reach level N to unlock … / You need N more gems for … | Shop level gate or gem balance | Earn more XP or gems |
| AI buttons do nothing or show unavailable | Gemini is not configured | Set GEMINI\_API\_KEY in the server's .env file |
